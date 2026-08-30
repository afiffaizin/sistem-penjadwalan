<?php

namespace App\Http\Controllers;

use App\Exports\JadwalExport;
use App\Jobs\GenerateJadwalJob;
use App\Models\Jadwal;
use App\Models\JadwalGenerateJob;
use App\Models\TahunAjar;
use App\Services\JadwalViewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $tahunAjarAktifLabel = TahunAjar::where('is_active', true)->first() ?? $daftarTahunAjar->first();
            $selectedTahunAjarId = $tahunAjarAktifLabel ? $tahunAjarAktifLabel->id : null;
        }

        $jadwalList = [];
        $activeJob = null;
        if ($selectedTahunAjarId) {
            $jadwalList = Jadwal::with(['dosen', 'mata_kuliah', 'kelas', 'ruang'])
                ->where('tahun_ajar_id', $selectedTahunAjarId)
                ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')")
                ->orderBy('sesi_mulai')
                ->paginate(25)
                ->withQueryString();

            $activeJob = JadwalGenerateJob::where('tahun_ajar_id', $selectedTahunAjarId)
                ->whereIn('status', ['pending', 'processing'])
                ->latest()
                ->first();
        }

        return view('generate-jadwal', [
            'daftarTahunAjar' => $daftarTahunAjar,
            'tahunAjarAktif' => $tahunAjarAktifLabel,
            'jadwalList' => $jadwalList,
            'activeJob' => $activeJob,
            'hasTahunAjar' => $daftarTahunAjar->isNotEmpty(),
        ]);
    }

    public function generate(Request $request)
    {
        $targetTahunAjarId = $request->input('tahun_ajar_id');

        if (!$targetTahunAjarId) {
            return response()->json(['status' => 'error', 'message' => 'Tahun Ajar tidak valid.'], 422);
        }

        // Prevent duplicate concurrent jobs
        if (JadwalGenerateJob::hasActiveJob($targetTahunAjarId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proses generate jadwal untuk tahun ajar ini sedang berjalan. Silakan tunggu hingga selesai.',
            ], 409);
        }

        // Create tracking record
        $tracker = JadwalGenerateJob::create([
            'tahun_ajar_id' => $targetTahunAjarId,
            'status' => 'pending',
        ]);

        // Dispatch background job
        GenerateJadwalJob::dispatch($targetTahunAjarId, $tracker->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Proses generate jadwal dimulai.',
            'job_id' => $tracker->id,
        ]);
    }

    public function generateStatus(Request $request)
    {
        $tahunAjarId = $request->input('tahun_ajar_id');

        if (!$tahunAjarId) {
            return response()->json(['status' => 'error', 'message' => 'Tahun Ajar tidak valid.'], 422);
        }

        $job = JadwalGenerateJob::latestForTahunAjar($tahunAjarId);

        if (!$job) {
            return response()->json(['job_status' => 'none']);
        }

        return response()->json([
            'job_id' => $job->id,
            'job_status' => $job->status,
            'error_message' => $job->error_message,
            'started_at' => $job->started_at?->toDateTimeString(),
            'completed_at' => $job->completed_at?->toDateTimeString(),
            'created_at' => $job->created_at?->toDateTimeString(),
        ]);
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

        $hariBaru = $request->hari_baru;
        $sesiMulaiBaru = (int) $request->sesi_mulai_baru;

        try {
            DB::transaction(function () use ($request, $hariBaru, $sesiMulaiBaru) {

                // Lock jadwal target agar tidak ada race condition
                $jadwalTarget = Jadwal::lockForUpdate()->findOrFail($request->jadwal_id);

                // Hitung durasi asli blok matkul
                $durasiAsli = ($jadwalTarget->sesi_selesai - $jadwalTarget->sesi_mulai) + 1;
                $sesiSelesaiBaru = $sesiMulaiBaru + $durasiAsli - 1;

                // Batasi agar pergeseran tidak melebihi batas maksimum sesi harian (sesi 8)
                if ($sesiSelesaiBaru > 8) {
                    throw new \Exception("Gagal! Matkul berdurasi {$durasiAsli} sesi, tidak muat jika dimulai dari Sesi {$sesiMulaiBaru}.");
                }

                // TUKAR POSISI (SWAP)
                if ($request->has('mode_tukar')) {
                    // Cari jadwal apa saja yang konflik dengan posisi baru (bisa bentrok kelas, dosen, atau ruangan)
                    $konflik = Jadwal::lockForUpdate()->where('hari', $hariBaru)
                        ->where('id', '!=', $jadwalTarget->id)
                        ->where(function ($q) use ($sesiMulaiBaru, $sesiSelesaiBaru) {
                            $q->where('sesi_mulai', '<=', $sesiSelesaiBaru)
                                ->where('sesi_selesai', '>=', $sesiMulaiBaru);
                        })
                        ->where(function ($q) use ($jadwalTarget) {
                            $q->where('kelas_id', $jadwalTarget->kelas_id)
                              ->orWhere('dosen_id', $jadwalTarget->dosen_id)
                              ->orWhere('ruang_id', $jadwalTarget->ruang_id);
                        })->get();

                    if ($konflik->count() > 1) {
                        throw new \Exception("Gagal Tukar: Terdapat lebih dari 1 jadwal yang bertabrakan di slot tersebut. Tidak dapat melakukan swap otomatis.");
                    }

                    $jadwalTabrakan = $konflik->first();

                    if ($jadwalTabrakan) {
                        // Simpan posisi lama Jadwal Target untuk barter posisi
                        $hariLama = $jadwalTarget->hari;
                        $sesiMulaiLama = $jadwalTarget->sesi_mulai;

                        // Hitung durasi masing-masing agar swap mempertahankan durasi asli
                        $durasiTabrakan = ($jadwalTabrakan->sesi_selesai - $jadwalTabrakan->sesi_mulai) + 1;
                        $sesiSelesaiLamaBaru = $sesiMulaiLama + $durasiTabrakan - 1;

                        // Pastikan jadwal tabrakan muat di posisi lama target
                        if ($sesiSelesaiLamaBaru > 8) {
                            throw new \Exception("Gagal Tukar: Jadwal yang ditukar berdurasi {$durasiTabrakan} sesi, tidak muat di posisi asal (Sesi {$sesiMulaiLama}).");
                        }

                        // CEK BENTROK UNTUK JADWAL TARGET DI POSISI BARU (abaikan jadwalTabrakan)
                        $cekBentrokTarget = Jadwal::where('hari', $hariBaru)
                            ->whereNotIn('id', [$jadwalTarget->id, $jadwalTabrakan->id])
                            ->where(function ($q) use ($sesiMulaiBaru, $sesiSelesaiBaru) {
                                $q->where('sesi_mulai', '<=', $sesiSelesaiBaru)
                                    ->where('sesi_selesai', '>=', $sesiMulaiBaru);
                            });

                        if ((clone $cekBentrokTarget)->where('kelas_id', $jadwalTarget->kelas_id)->exists()) {
                            throw new \Exception("Gagal Tukar: Kelas sudah memiliki jadwal matkul lain pada sesi tujuan.");
                        }
                        if ((clone $cekBentrokTarget)->where('dosen_id', $jadwalTarget->dosen_id)->exists()) {
                            throw new \Exception("Gagal Tukar: Dosen bersangkutan sedang mengajar di kelas lain pada sesi tujuan.");
                        }
                        if ((clone $cekBentrokTarget)->where('ruang_id', $jadwalTarget->ruang_id)->exists()) {
                            throw new \Exception("Gagal Tukar: Ruangan tersebut sedang digunakan untuk perkuliahan lain pada sesi tujuan.");
                        }

                        // CEK BENTROK UNTUK JADWAL TABRAKAN DI POSISI LAMA TARGET (abaikan jadwalTarget)
                        $cekBentrokTabrakan = Jadwal::where('hari', $hariLama)
                            ->whereNotIn('id', [$jadwalTarget->id, $jadwalTabrakan->id])
                            ->where(function ($q) use ($sesiMulaiLama, $sesiSelesaiLamaBaru) {
                                $q->where('sesi_mulai', '<=', $sesiSelesaiLamaBaru)
                                    ->where('sesi_selesai', '>=', $sesiMulaiLama);
                            });

                        if ((clone $cekBentrokTabrakan)->where('kelas_id', $jadwalTabrakan->kelas_id)->exists()) {
                            throw new \Exception("Gagal Tukar: Kelas jadwal yang ditukar sudah memiliki matkul lain pada sesi asal.");
                        }
                        if ((clone $cekBentrokTabrakan)->where('dosen_id', $jadwalTabrakan->dosen_id)->exists()) {
                            throw new \Exception("Gagal Tukar: Dosen jadwal yang ditukar sedang mengajar di kelas lain pada sesi asal.");
                        }
                        if ((clone $cekBentrokTabrakan)->where('ruang_id', $jadwalTabrakan->ruang_id)->exists()) {
                            throw new \Exception("Gagal Tukar: Ruangan jadwal yang ditukar sedang digunakan pada sesi asal.");
                        }

                        // Pindahkan jadwal yang ditabrak ke posisi lama jadwal target (durasi dipertahankan)
                        $jadwalTabrakan->update([
                            'hari' => $hariLama,
                            'sesi_mulai' => $sesiMulaiLama,
                            'sesi_selesai' => $sesiSelesaiLamaBaru
                        ]);

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
