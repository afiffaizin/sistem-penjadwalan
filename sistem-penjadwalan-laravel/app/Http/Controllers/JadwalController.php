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
        $pengampu = DosenMatkul::with(['mata_kuliah', 'dosen', 'kelas'])
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
                    'dosen_nama' => $item->dosen->nama ?? '-',
                    'mata_kuliah_id' => $item->mata_kuliah_id,
                    'mata_kuliah_nama' => $item->mata_kuliah->nama ?? '-',
                    'kelas_id' => $item->kelas_id,
                    'kelas_nama' => $item->kelas->nama ?? '-',
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
            return back()->with('error',
                '<strong>❌ Pembuatan jadwal gagal.</strong><br><br>' .
                '<strong>Alasan:</strong><br>Data tidak lengkap.<br>' .
                '<ul class="list-disc pl-5 mt-1 space-y-1 text-sm text-red-600"><li>Belum ada data ploting dosen atau data ruangan pada semester ini.</li></ul><br>' .
                '<strong>Rekomendasi:</strong><br>• Silakan lengkapi data ploting dosen mengajar dan data ruangan terlebih dahulu, lalu coba kembali.'
            );
        }

        try {
            // 4. Kirim data ke Python
            $response = Http::timeout(700)
                ->post(config('services.python.url') . '/api/generate-jadwal', [
                    'pengampu' => $pengampu,
                    'ruangan' => $ruangan,
                    'unavailable_days' => $unavailableDays,
                ]);

            if ($response->failed()) {
                return back()->with('error',
                    '<strong>❌ Pembuatan jadwal gagal.</strong><br><br>' .
                    '<strong>Alasan:</strong><br>Kesalahan koneksi.<br>' .
                    '<ul class="list-disc pl-5 mt-1 space-y-1 text-sm text-red-600"><li>Gagal terhubung ke server penjadwalan.</li></ul><br>' .
                    '<strong>Rekomendasi:</strong><br>• Pastikan server penjadwalan (Uvicorn) sedang berjalan dan coba lagi.'
                );
            }

            $hasil = $response->json();

            if (isset($hasil['status_solver']) && $hasil['status_solver'] === 'GAGAL') {
                $pesanError = '<strong>❌ Pembuatan jadwal gagal.</strong><br><br>';
                
                if (!empty($hasil['pesan'])) {
                    $pesanError .= '<strong>Alasan:</strong><br>' . e($hasil['pesan']);
                } else {
                    $pesanError .= '<strong>Alasan:</strong><br>Terjadi kendala pada proses penjadwalan.';
                }

                if (!empty($hasil['violations'])) {
                    $pesanError .= '<ul class="list-disc pl-5 mt-1 space-y-1 text-sm text-red-600">';
                    foreach ($hasil['violations'] as $v) {
                        // Parse **bold** syntax to <strong> HTML tag securely
                        $formatted_v = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', e($v));
                        $pesanError .= '<li>' . $formatted_v . '</li>';
                    }
                    $pesanError .= '</ul>';
                }

                if (!empty($hasil['recommendation'])) {
                    $pesanError .= '<br><br><strong>Rekomendasi:</strong><br>• ' . e($hasil['recommendation']);
                }

                return back()->with('error', $pesanError);
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
                return back()->with('error',
                    '<strong>❌ Pembuatan jadwal gagal.</strong><br><br>' .
                    '<strong>Alasan:</strong><br>Kesalahan database.<br>' .
                    '<ul class="list-disc pl-5 mt-1 space-y-1 text-sm text-red-600"><li>Gagal menyimpan jadwal yang dibuat ke dalam database.</li></ul><br>' .
                    '<strong>Rekomendasi:</strong><br>• Silakan coba lagi. Jika masalah berlanjut, hubungi administrator sistem.'
                );
            }
        } catch (\Exception $e) {
            return back()->with('error',
                '<strong>❌ Pembuatan jadwal gagal.</strong><br><br>' .
                '<strong>Alasan:</strong><br>Kesalahan sistem.<br>' .
                '<ul class="list-disc pl-5 mt-1 space-y-1 text-sm text-red-600"><li>Terjadi kesalahan sistem yang tidak terduga.</li></ul><br>' .
                '<strong>Rekomendasi:</strong><br>• Silakan coba lagi. Jika masalah berlanjut, hubungi administrator sistem.'
            );
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
