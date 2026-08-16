<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Ruang;
use App\Models\TahunAjar;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class JadwalViewService
{
    private static array $hariKerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
    private static int $totalSesi = 8;

    /**
     * Build jadwal matrix view untuk halaman Hasil Jadwal (sekretaris + global).
     * Mendukung tahun_ajar selector, semua filter, export, dan edit mode.
     */
    public function build(Request $request): array
    {
        $daftarTahunAjar = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        $targetTahunAjarId = $request->input('tahun_ajar_id');
        $activeTahunAjarIds = $targetTahunAjarId ? [$targetTahunAjarId] : [];

        $matrixJadwal = self::emptyMatrix();
        $matkulMandiri = collect();
        $jadwalDicari = !empty($targetTahunAjarId);

        if (!empty($activeTahunAjarIds) && $jadwalDicari) {
            $query = Jadwal::with(['mata_kuliah', 'dosen', 'kelas', 'ruang'])
                ->whereIn('tahun_ajar_id', $activeTahunAjarIds);

            if ($request->filled('dosen_id')) $query->where('dosen_id', $request->dosen_id);
            if ($request->filled('kelas_id')) $query->where('kelas_id', $request->kelas_id);
            if ($request->filled('ruang_id')) $query->where('ruang_id', $request->ruang_id);
            if ($request->filled('prodi_id')) {
                $query->whereHas('kelas', fn ($q) => $q->where('prodi_id', $request->prodi_id));
            }

            $matrixJadwal = self::mapToMatrix($query->get());

            if ($request->filled('kelas_id') || $request->filled('prodi_id')) {
                $kelasTarget = Kelas::query();
                if ($request->filled('kelas_id')) $kelasTarget->where('id', $request->kelas_id);
                if ($request->filled('prodi_id')) $kelasTarget->where('prodi_id', $request->prodi_id);
                $matkulMandiri = self::detectMbkm($kelasTarget->get());
            }
        }

        return [
            'daftarTahunAjar' => $daftarTahunAjar,
            'targetTahunAjarId' => $targetTahunAjarId,
            'dosens' => Dosen::whereIn('id', Jadwal::whereIn('tahun_ajar_id', $activeTahunAjarIds)->distinct()->pluck('dosen_id'))->orderBy('nama')->get(),
            'kelas' => Kelas::whereIn('tahun_ajar_id', $activeTahunAjarIds)->orderBy('nama')->get(),
            'ruangs' => Ruang::whereIn('tahun_ajar_id', $activeTahunAjarIds)->orderBy('nama')->get(),
            'prodis' => ProgramStudi::orderBy('nama')->get(),
            'matrixJadwal' => $matrixJadwal,
            'matkulMandiri' => $matkulMandiri,
            'totalSesi' => self::$totalSesi,
            'hariKerja' => self::$hariKerja,
            'filterLabels' => $this->filterLabels($request, $targetTahunAjarId),
            'tampilPerKelas' => !$request->filled('dosen_id') && !$request->filled('kelas_id') && !$request->filled('ruang_id'),
        ];
    }

    /**
     * Build jadwal matrix untuk halaman publik / kajur (hanya active tahun_ajar, filter by request).
     */
    public function buildPublic(Request $request): array
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        $selectedTahunAjarId = $request->input('tahun_ajar_id');
        
        $activeTahunAjarIds = $selectedTahunAjarId ? [$selectedTahunAjarId] : [];

        $dosenIds = Jadwal::whereIn('tahun_ajar_id', $activeTahunAjarIds)->distinct()->pluck('dosen_id');
        $dosens = Dosen::whereIn('id', $dosenIds)->orderBy('nama')->get();
        $kelas  = Kelas::whereIn('tahun_ajar_id', $activeTahunAjarIds)->orderBy('nama')->get();
        $ruangs = Ruang::whereIn('tahun_ajar_id', $activeTahunAjarIds)->orderBy('nama')->get();
        $prodis = ProgramStudi::orderBy('nama')->get();

        $matrixJadwal = self::emptyMatrix();
        $matkulMandiri = collect();

        if ($request->anyFilled(['dosen_id', 'kelas_id', 'ruang_id', 'prodi_id', 'tahun_ajar_id'])) {
            $query = Jadwal::with(['mata_kuliah', 'dosen', 'kelas', 'ruang'])
                ->whereIn('tahun_ajar_id', $activeTahunAjarIds);

            if ($request->filled('dosen_id')) $query->where('dosen_id', $request->dosen_id);
            if ($request->filled('kelas_id')) $query->where('kelas_id', $request->kelas_id);
            if ($request->filled('ruang_id')) $query->where('ruang_id', $request->ruang_id);
            if ($request->filled('prodi_id')) {
                $query->whereHas('kelas', fn ($q) => $q->where('prodi_id', $request->prodi_id));
            }

            $matrixJadwal = self::mapToMatrix($query->get());

            if ($request->filled('kelas_id') || $request->filled('prodi_id')) {
                $kelasTarget = Kelas::query();
                if ($request->filled('kelas_id')) $kelasTarget->where('id', $request->kelas_id);
                if ($request->filled('prodi_id')) $kelasTarget->where('prodi_id', $request->prodi_id);
                $matkulMandiri = self::detectMbkm($kelasTarget->get());
            }
        }

        return compact('dosens', 'kelas', 'ruangs', 'prodis', 'matrixJadwal', 'matkulMandiri', 'tahunAjars', 'selectedTahunAjarId') + [
            'totalSesi' => self::$totalSesi,
            'hariKerja' => self::$hariKerja,
        ];
    }

    /**
     * Build jadwal matrix scoped untuk prodi tertentu (kaprodi).
     */
    public function buildForProdi(Request $request, int $prodiId): array
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        $selectedTahunAjarId = $request->input('tahun_ajar_id');

        $kelasQuery = Kelas::where('prodi_id', $prodiId);
        if ($selectedTahunAjarId) {
            $kelasQuery->where('tahun_ajar_id', $selectedTahunAjarId);
        } else {
            // Jika tidak ada tahun ajar yang dipilih, tidak usah query kelas (mencegah dobel)
            $kelasQuery->where('id', -1);
        }
        $kelas = $kelasQuery->orderBy('nama')->get();
        $kelasIds = $kelas->pluck('id')->toArray();

        $dosenQuery = Jadwal::whereIn('kelas_id', $kelasIds);
        if ($selectedTahunAjarId) {
            $dosenQuery->where('tahun_ajar_id', $selectedTahunAjarId);
        }
        $dosenIds = $dosenQuery->distinct()->pluck('dosen_id');

        $dosens = Dosen::whereIn('id', $dosenIds)->orderBy('nama')->get();
        $ruangs = Ruang::when($selectedTahunAjarId, fn ($q) => $q->where('tahun_ajar_id', $selectedTahunAjarId))->orderBy('nama')->get();

        $matkulMandiri = self::detectMbkm($kelas);

        $query = Jadwal::with(['mata_kuliah', 'dosen', 'kelas', 'ruang'])
            ->whereIn('kelas_id', $kelasIds);

        if ($selectedTahunAjarId) {
            $query->where('tahun_ajar_id', $selectedTahunAjarId);
        }

        if ($request->filled('dosen_id')) $query->where('dosen_id', $request->dosen_id);
        if ($request->filled('kelas_id')) $query->where('kelas_id', $request->kelas_id);
        if ($request->filled('ruang_id')) $query->where('ruang_id', $request->ruang_id);

        $matrixJadwal = self::mapToMatrix($query->get());

        return compact('dosens', 'kelas', 'ruangs', 'matrixJadwal', 'matkulMandiri', 'tahunAjars', 'selectedTahunAjarId') + [
            'totalSesi' => self::$totalSesi,
            'hariKerja' => self::$hariKerja,
        ];
    }

    /**
     * Inisialisasi matriks jadwal kosong.
     */
    public static function emptyMatrix(): array
    {
        $matrix = [];
        for ($s = 1; $s <= self::$totalSesi; $s++) {
            foreach (self::$hariKerja as $hari) {
                $matrix[$s][$hari] = [];
            }
        }
        return $matrix;
    }

    /**
     * Map koleksi Jadwal ke struktur matriks [sesi][hari][].
     */
    public static function mapToMatrix(Collection $jadwals): array
    {
        $matrix = self::emptyMatrix();
        $colors = ['bg-pink-200', 'bg-blue-200', 'bg-yellow-200', 'bg-green-200', 'bg-purple-200', 'bg-teal-200', 'bg-red-200', 'bg-blue-50'];

        foreach ($jadwals as $j) {
            for ($s = (int) $j->sesi_mulai; $s <= (int) $j->sesi_selesai; $s++) {
                if ($s < 1 || $s > self::$totalSesi || !in_array($j->hari, self::$hariKerja, true)) continue;

                $matkulId = $j->mata_kuliah_id ?? 0;
                $matrix[$s][$j->hari][] = [
                    'id'           => $j->id,
                    'sesi_mulai'   => $j->sesi_mulai,
                    'sesi_selesai' => $j->sesi_selesai,
                    'hari'         => $j->hari,
                    'mata_kuliah'  => $j->mata_kuliah->nama ?? '-',
                    'dosen'        => $j->dosen->nama ?? '-',
                    'kelas'        => $j->kelas->nama ?? '-',
                    'ruang'        => $j->ruang->nama ?? '-',
                    'jenis'        => isset($j->ruang->kategori) ? ucfirst($j->ruang->kategori) : '-',
                    'warna'        => $colors[$matkulId % count($colors)],
                ];
            }
        }

        return $matrix;
    }

    /**
     * Deteksi mata kuliah MBKM/Mandiri berdasarkan kelas dan nama matkul.
     * Satu tempat untuk semua logika MBKM — tidak perlu duplikasi di controller lain.
     */
    public static function detectMbkm(Collection|\Illuminate\Database\Eloquent\Collection $kelasList): Collection
    {
        $matkulMandiri = collect();

        $mbkmGlobal = MataKuliah::where(function ($q) {
            $q->whereRaw("LOWER(nama) LIKE '%magang%'")
                ->orWhereRaw("LOWER(nama) LIKE '%tugas akhir%'")
                ->orWhereRaw("LOWER(nama) LIKE '%proyek keamanan%'");
        })->get()->unique('nama');

        foreach ($kelasList as $k) {
            $namaKelasLower = strtolower($k->nama);
            preg_match('/\d/', $k->nama, $matches);
            $tingkatAngka = isset($matches[0]) ? (int) $matches[0] : 0;
            $insertedMbkm = [];

            foreach ($mbkmGlobal as $mGlob) {
                $namaMatkulLower = strtolower($mGlob->nama);

                $matchRks3 = str_contains($namaKelasLower, 'rks') && $tingkatAngka === 3
                    && (str_contains($namaMatkulLower, 'magang') || str_contains($namaMatkulLower, 'proyek keamanan'));
                $matchRks4 = str_contains($namaKelasLower, 'rks') && $tingkatAngka === 4
                    && (str_contains($namaMatkulLower, 'tugas akhir') || str_contains($namaMatkulLower, 'akhir'));
                $matchTi3 = str_contains($namaKelasLower, 'ti') && $tingkatAngka === 3
                    && (str_contains($namaMatkulLower, 'tugas akhir') || str_contains($namaMatkulLower, 'akhir'));

                if (($matchRks3 || $matchRks4 || $matchTi3) && !in_array($namaMatkulLower, $insertedMbkm, true)) {
                    $matkulMandiri->push(['nama_matkul' => $mGlob->nama, 'nama_dosen' => 'Mandiri', 'kelas' => $k->nama]);
                    $insertedMbkm[] = $namaMatkulLower;
                }
            }
        }

        return $matkulMandiri;
    }

    private function filterLabels(Request $request, $targetTahunAjarId): array
    {
        $ta = $targetTahunAjarId ? TahunAjar::find($targetTahunAjarId) : null;

        return [
            'Tahun Akademik' => $ta ? $ta->tahun . ' ' . ucfirst($ta->semester) : 'Semua',
            'Program Studi' => $request->filled('prodi_id') ? (ProgramStudi::find($request->prodi_id)->nama ?? '-') : 'Semua',
            'Kelas' => $request->filled('kelas_id') ? (Kelas::find($request->kelas_id)->nama ?? '-') : 'Semua',
            'Dosen' => $request->filled('dosen_id') ? (Dosen::find($request->dosen_id)->nama ?? '-') : 'Semua',
            'Ruangan' => $request->filled('ruang_id') ? (Ruang::find($request->ruang_id)->nama ?? '-') : 'Semua',
        ];
    }
}
