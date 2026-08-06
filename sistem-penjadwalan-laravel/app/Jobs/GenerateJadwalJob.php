<?php

namespace App\Jobs;

use App\Models\DosenMatkul;
use App\Models\DosenUnavailableDay;
use App\Models\Jadwal;
use App\Models\JadwalGenerateJob;
use App\Models\Ruang;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateJadwalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 0; // no timeout — solver can run 15+ min

    /**
     * Prevent queue from retrying this job while it's still running.
     * Gives 20 minutes before the queue considers it "timed out".
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(20);
    }

    public function __construct(
        public int $tahunAjarId,
        public int $trackingJobId
    ) {}

    public function handle(): void
    {
        $tracker = JadwalGenerateJob::find($this->trackingJobId);
        if (!$tracker) {
            return;
        }

        $tracker->update(['status' => 'processing', 'started_at' => now()]);

        try {
            // 1. Collect data
            $pengampu = $this->collectPengampu();
            $ruangan = $this->collectRuangan();
            $unavailableDays = $this->collectUnavailableDays();

            if (empty($pengampu) || empty($ruangan)) {
                $this->markFailed($tracker, 'Data tidak lengkap. Belum ada data ploting dosen atau data ruangan pada semester ini.');
                return;
            }

            // 2. Call Python solver
            $response = Http::timeout(960)
                ->post(config('services.python.url') . '/api/generate-jadwal', [
                    'pengampu' => $pengampu,
                    'ruangan' => $ruangan,
                    'unavailable_days' => $unavailableDays,
                ]);

            if ($response->failed()) {
                $this->markFailed($tracker, 'Gagal terhubung ke server penjadwalan. Pastikan server Python (Uvicorn) sedang berjalan.');
                return;
            }

            $hasil = $response->json();

            if (isset($hasil['status_solver']) && $hasil['status_solver'] === 'GAGAL') {
                $errorMsg = $hasil['pesan'] ?? 'Terjadi kendala pada proses penjadwalan.';

                if (!empty($hasil['violations'])) {
                    $errorMsg .= "\n" . implode("\n", $hasil['violations']);
                }
                if (!empty($hasil['recommendation'])) {
                    $errorMsg .= "\n\nRekomendasi: " . $hasil['recommendation'];
                }

                $this->markFailed($tracker, $errorMsg);
                return;
            }

            // 3. Save results
            DB::transaction(function () use ($hasil) {
                Jadwal::where('tahun_ajar_id', $this->tahunAjarId)->delete();

                foreach ($hasil['data'] as $j) {
                    Jadwal::create([
                        'tahun_ajar_id' => $this->tahunAjarId,
                        'dosen_id' => $j['dosen_id'],
                        'mata_kuliah_id' => $j['mata_kuliah_id'],
                        'kelas_id' => $j['kelas_id'],
                        'ruang_id' => $j['ruang_id'],
                        'hari' => $j['hari'],
                        'sesi_mulai' => $j['sesi_mulai'],
                        'sesi_selesai' => $j['sesi_selesai'],
                    ]);
                }
            });

            $tracker->update(['status' => 'completed', 'completed_at' => now()]);

        } catch (\Throwable $e) {
            Log::error('GenerateJadwalJob failed', [
                'tahun_ajar_id' => $this->tahunAjarId,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($tracker, 'Kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $tracker = JadwalGenerateJob::find($this->trackingJobId);
        if ($tracker && $tracker->isActive()) {
            $this->markFailed($tracker, 'Job gagal dieksekusi: ' . ($exception?->getMessage() ?? 'Unknown error'));
        }
    }

    private function markFailed(JadwalGenerateJob $tracker, string $message): void
    {
        $tracker->update([
            'status' => 'failed',
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }

    private function collectPengampu(): array
    {
        return DosenMatkul::with(['mata_kuliah', 'dosen', 'kelas'])
            ->where('tahun_ajar_id', $this->tahunAjarId)
            ->get()
            ->reject(function ($item) {
                if (!$item->mata_kuliah) {
                    return true;
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
                    'group_matkul' => $item->mata_kuliah->kode_group ?? '-',
                    'kelas_id' => $item->kelas_id,
                    'kelas_nama' => $item->kelas->nama ?? '-',
                    'tahun_ajar_id' => $item->tahun_ajar_id,
                    'prodi_id' => $item->mata_kuliah->prodi_id ?? null,
                    'jam_teori' => $sksTeori * 1,
                    'jam_praktikum' => $sksPraktikum * 2,
                ];
            })
            ->values()
            ->toArray();
    }

    private function collectRuangan(): array
    {
        return Ruang::where('tahun_ajar_id', $this->tahunAjarId)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'nama' => $r->nama,
                    'kategori' => strtolower($r->kategori),
                    'prodi_id' => $r->prodi_id,
                    'spesifik_mk' => $r->spesifik_mk,
                ];
            })
            ->toArray();
    }

    private function collectUnavailableDays(): array
    {
        return DosenUnavailableDay::where('tahun_ajar_id', $this->tahunAjarId)
            ->get()
            ->map(function ($item) {
                return [
                    'dosen_id' => $item->dosen_id,
                    'hari' => $item->hari,
                ];
            })
            ->values()
            ->toArray();
    }
}
