<?php

namespace App\Http\Controllers;

use App\Exports\JadwalExport;
use App\Models\Dosen;
use App\Models\DosenMatkul;
use App\Models\DosenUnavailableDay;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Ruang;
use App\Models\TahunAjar;
use App\Services\JadwalViewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;

class JadwalController extends Controller
{
    public function index(Request $request)
    {
        $daftarTahunAjar = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();

        if ($request->has('tahun_ajar_id')) {
            $selectedTahunAjarId = $request->tahun_ajar_id;
            $tahunAjarAktifLabel = TahunAjar::find($selectedTahunAjarId);
        } else {
            $tahunAjarAktifLabel = TahunAjar::where('is_active', true)->first();
            $selectedTahunAjarId = $tahunAjarAktifLabel ? $tahunAjarAktifLabel->id : null;
        }

        $jadwalList = [];
        if ($selectedTahunAjarId) {
            $jadwalList = Jadwal::with(['dosen', 'mata_kuliah', 'kelas', 'ruang'])
                ->where('tahun_ajar_id', $selectedTahunAjarId)
                ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')")
                ->orderBy('sesi_mulai')
                ->get();
        }

        return view('generate-jadwal', [
            'daftarTahunAjar' => $daftarTahunAjar,
            'tahunAjarAktif' => $tahunAjarAktifLabel,
            'jadwalList' => $jadwalList
        ]);
    }

    public function generate(Request $request)
    {
        // 1. Ambil ID Tahun Ajar yang dipilih dari dropdown form
        $targetTahunAjarId = $request->input('tahun_ajar_id');

        if (!$targetTahunAjarId) {
            return back()->with('error', 'Tahun Ajar tidak valid.');
        }

        // 2. Tarik Data Dosen
        $pengampu = DosenMatkul::with('mata_kuliah')
            ->where('tahun_ajar_id', $targetTahunAjarId)
            ->get()
            ->reject(function ($item) {
                if (!$item->mata_kuliah) {
                    return false;
                }
                $namaMk = strtolower($item->mata_kuliah->nama);
                return str_contains($namaMk, 'proyek keamanan siber') ||
                    str_contains($namaMk, 'magang') ||
                    str_contains($namaMk, 'tugas akhir');
            })
            ->map(function ($item) {
                $sksTeori = $item->mata_kuliah->sks_teori ?? 0;
                $sksPraktikum = $item->mata_kuliah->sks_praktikum ?? 0;

                return [
                    'id' => $item->id,
                    'dosen_id' => $item->dosen_id,
                    'mata_kuliah_id' => $item->mata_kuliah_id,
                    'kelas_id' => $item->kelas_id,
                    'tahun_ajar_id' => $item->tahun_ajar_id,
                    'jam_teori' => $sksTeori * 1,
                    'jam_praktikum' => $sksPraktikum * 2
                ];
            })
            ->values()
            ->toArray();

        // 3. Tarik Data Ruangan
        $ruangan = Ruang::all()->map(function ($r) {
            return [
                'id' => $r->id,
                'nama' => $r->nama,
                'kategori' => strtolower($r->kategori)
            ];
        })->toArray();

        $unavailableDays = DosenUnavailableDay::where('tahun_ajar_id', $targetTahunAjarId)
            ->get()
            ->map(function ($item) {
                return [
                    'dosen_id' => $item->dosen_id,
                    'hari' => $item->hari,
                ];
            })
            ->values()
            ->toArray();

        if (empty($pengampu) || empty($ruangan)) {
            return back()->with('error', 'Data Pengampu atau Ruangan pada semester ini masih kosong.');
        }

        try {
            // 4. Kirim data ke Python
            $response = Http::timeout(400)
                ->post(config('services.python.url') . '/api/generate-jadwal', [
                    'pengampu' => $pengampu,
                    'ruangan' => $ruangan,
                    'unavailable_days' => $unavailableDays,
                ]);

            if ($response->failed()) {
                return back()->with('error', 'Gagal terhubung ke Python. Pastikan server Uvicorn berjalan.');
            }

            $hasil = $response->json();

            if (isset($hasil['status_solver']) && $hasil['status_solver'] === 'GAGAL') {
                return back()->with('error', 'Sistem Gagal menemukan jadwal: ' . $hasil['pesan']);
            }

            // 5. Simpan ke Database
            DB::beginTransaction();
            try {
                Jadwal::where('tahun_ajar_id', $targetTahunAjarId)->delete();

                foreach ($hasil['data'] as $j) {
                    Jadwal::create([
                        'tahun_ajar_id' => $targetTahunAjarId,
                        'dosen_id' => $j['dosen_id'],
                        'mata_kuliah_id' => $j['mata_kuliah_id'],
                        'kelas_id' => $j['kelas_id'],
                        'ruang_id' => $j['ruang_id'],
                        'hari' => $j['hari'],
                        'sesi_mulai' => $j['sesi_mulai'],
                        'sesi_selesai' => $j['sesi_selesai'],
                    ]);
                }

                DB::commit();

                return redirect()->route('jadwal.index', ['tahun_ajar_id' => $targetTahunAjarId])
                    ->with('success', 'Jadwal berhasil digenerate khusus untuk semester ini tanpa menghapus data semester lain!');
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Gagal menyimpan jadwal ke database: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function deleteByTahunAjar(Request $request)
    {
        $tahunAjarId = $request->input('tahun_ajar_id');

        if (!$tahunAjarId) {
            return back()->with('error', 'Tahun Ajar tidak valid.');
        }

        try {
            $jumlah = Jadwal::where('tahun_ajar_id', $tahunAjarId)->count();
            Jadwal::where('tahun_ajar_id', $tahunAjarId)->delete();

            return redirect()
                ->route('jadwal.index', ['tahun_ajar_id' => $tahunAjarId])
                ->with('success', "Berhasil menghapus {$jumlah} data jadwal untuk semester yang dipilih.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus jadwal: ' . $e->getMessage());
        }
    }

    private function getWarna($id)
    {
        $colors = [
            'bg-pink-200',
            'bg-blue-200',
            'bg-yellow-200',
            'bg-green-200',
            'bg-purple-200',
            'bg-teal-200',
            'bg-red-200'
        ];
        return $colors[$id % count($colors)];
    }

    public function matrixjadwal(Request $request, JadwalViewService $jadwalViewService)
    {
        return view('jadwal.index', $jadwalViewService->build($request));
    }

    public function prosesUbahJadwal(Request $request)
    {
        $request->validate([
            'jadwal_id'       => 'required|exists:jadwals,id',
            'hari_baru'       => 'required|string',
            'sesi_mulai_baru' => 'required|integer|min:1|max:8',
        ]);

        $jadwalTarget = Jadwal::findOrFail($request->jadwal_id);

        // Hitung durasi asli blok matkul
        $durasiAsli = ($jadwalTarget->sesi_selesai - $jadwalTarget->sesi_mulai) + 1;

        $hariBaru = $request->hari_baru;
        $sesiMulaiBaru = (int) $request->sesi_mulai_baru;
        $sesiSelesaiBaru = $sesiMulaiBaru + $durasiAsli - 1;

        // Batasi agar pergeseran tidak melebihi batas maksimum sesi harian (sesi 8)
        if ($sesiSelesaiBaru > 8) {
            return redirect()->back()->with('error', "Gagal! Matkul berdurasi {$durasiAsli} sesi, tidak muat jika dimulai dari Sesi {$sesiMulaiBaru}.");
        }

        try {
            DB::transaction(function () use ($request, $jadwalTarget, $hariBaru, $sesiMulaiBaru, $sesiSelesaiBaru) {

                // TUKAR POSISI (SWAP)
                if ($request->has('mode_tukar')) {
                    // Cari jadwal lain yang menempati slot tujuan
                    $jadwalTabrakan = Jadwal::where('hari', $hariBaru)
                        ->where('kelas_id', $jadwalTarget->kelas_id)
                        ->where('id', '!=', $jadwalTarget->id)
                        ->where(function ($q) use ($sesiMulaiBaru, $sesiSelesaiBaru) {
                            $q->where('sesi_mulai', '<=', $sesiSelesaiBaru)
                                ->where('sesi_selesai', '>=', $sesiMulaiBaru);
                        })->first();

                    if ($jadwalTabrakan) {
                        // Simpan posisi lama Jadwal Target untuk barter posisi
                        $posisiLamaTarget = [
                            'hari' => $jadwalTarget->hari,
                            'sesi_mulai' => $jadwalTarget->sesi_mulai,
                            'sesi_selesai' => $jadwalTarget->sesi_selesai
                        ];

                        // Pindahkan jadwal yang ditabrak ke posisi lama jadwal target
                        $jadwalTabrakan->update($posisiLamaTarget);

                        // Pindahkan jadwal target ke posisi baru
                        $jadwalTarget->update([
                            'hari' => $hariBaru,
                            'sesi_mulai' => $sesiMulaiBaru,
                            'sesi_selesai' => $sesiSelesaiBaru
                        ]);
                        return;
                    }
                }

                // MODE PERGESERAN BIASA (MOVE)
                $cekBentrok = Jadwal::where('hari', $hariBaru)
                    ->where('id', '!=', $jadwalTarget->id)
                    ->where(function ($q) use ($sesiMulaiBaru, $sesiSelesaiBaru) {
                        $q->where('sesi_mulai', '<=', $sesiSelesaiBaru)
                            ->where('sesi_selesai', '>=', $sesiMulaiBaru);
                    });

                // 1. Cek Bentrok Kelas
                $bentrokKelas = (clone $cekBentrok)->where('kelas_id', $jadwalTarget->kelas_id)->first();
                if ($bentrokKelas) throw new \Exception("Kelas sudah memiliki jadwal matkul: " . $bentrokKelas->mata_kuliah->nama);

                // 2. Cek Bentrok Dosen
                $bentrokDosen = (clone $cekBentrok)->where('dosen_id', $jadwalTarget->dosen_id)->first();
                if ($bentrokDosen) throw new \Exception("Dosen bersangkutan sedang mengajar di kelas lain pada sesi ini.");

                // 3. Cek Bentrok Ruangan
                $bentrokRuang = (clone $cekBentrok)->where('ruang_id', $jadwalTarget->ruang_id)->first();
                if ($bentrokRuang) throw new \Exception("Ruangan tersebut sedang digunakan untuk perkuliahan lain.");

                // Eksekusi Update Blok secara utuh jika aman
                $jadwalTarget->update([
                    'hari' => $hariBaru,
                    'sesi_mulai' => $sesiMulaiBaru,
                    'sesi_selesai' => $sesiSelesaiBaru
                ]);
            });

            return redirect()->back()->with('success', 'Blok jadwal kuliah berhasil disesuaikan secara utuh!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memindahkan: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request, JadwalViewService $jadwalViewService)
    {
        $data = $jadwalViewService->build($request);
        $namaFile = 'Hasil_Jadwal_Kuliah_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new JadwalExport($data), $namaFile);
    }

    public function exportPdf(Request $request, JadwalViewService $jadwalViewService)
    {
        $data = $jadwalViewService->build($request);
        $pdf = Pdf::loadView('exports.jadwal_pdf', $data)->setPaper('a4', 'landscape');
        $namaFile = 'Hasil_Jadwal_Kuliah_' . date('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($namaFile);
    }
}
