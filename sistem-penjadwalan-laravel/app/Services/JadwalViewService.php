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

class JadwalViewService
{
    public function build(Request $request): array
    {
        $daftarTahunAjar = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        $targetTahunAjarId = $request->filled('tahun_ajar_id')
            ? $request->tahun_ajar_id
            : TahunAjar::where('is_active', true)->value('id');
        $activeTahunAjarIds = $targetTahunAjarId ? [$targetTahunAjarId] : [];

        $hariKerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $totalSesi = 8;
        $matrixJadwal = [];

        for ($s = 1; $s <= $totalSesi; $s++) {
            foreach ($hariKerja as $hari) {
                $matrixJadwal[$s][$hari] = [];
            }
        }

        $matkulMandiri = collect();
        $jadwalDicari = $request->has('tahun_ajar_id') || $request->anyFilled(['dosen_id', 'kelas_id', 'ruang_id', 'prodi_id']);

        if (!empty($activeTahunAjarIds) && $jadwalDicari) {
            $query = Jadwal::with(['mata_kuliah', 'dosen', 'kelas', 'ruang'])
                ->whereIn('tahun_ajar_id', $activeTahunAjarIds);

            if ($request->filled('dosen_id')) $query->where('dosen_id', $request->dosen_id);
            if ($request->filled('kelas_id')) $query->where('kelas_id', $request->kelas_id);
            if ($request->filled('ruang_id')) $query->where('ruang_id', $request->ruang_id);
            if ($request->filled('prodi_id')) {
                $query->whereHas('kelas', fn ($q) => $q->where('prodi_id', $request->prodi_id));
            }

            foreach ($query->get() as $j) {
                for ($s = (int) $j->sesi_mulai; $s <= (int) $j->sesi_selesai; $s++) {
                    if ($s < 1 || $s > $totalSesi || !in_array($j->hari, $hariKerja, true)) continue;

                    $matrixJadwal[$s][$j->hari][] = [
                        'id'           => $j->id,
                        'sesi_mulai'   => $j->sesi_mulai,
                        'sesi_selesai' => $j->sesi_selesai,
                        'hari'         => $j->hari,
                        'mata_kuliah'  => $j->mata_kuliah->nama ?? '-',
                        'dosen'        => $j->dosen->nama ?? '-',
                        'kelas'        => $j->kelas->nama ?? '-',
                        'ruang'        => $j->ruang->nama ?? '-',
                        'jenis'        => isset($j->ruang->kategori) ? ucfirst($j->ruang->kategori) : '-',
                        'warna'        => $this->getWarna($j->mata_kuliah_id ?? 0),
                    ];
                }
            }

            if ($request->filled('kelas_id') || $request->filled('prodi_id')) {
                $kelasTarget = Kelas::query();
                if ($request->filled('kelas_id')) $kelasTarget->where('id', $request->kelas_id);
                if ($request->filled('prodi_id')) $kelasTarget->where('prodi_id', $request->prodi_id);

                $mbkmGlobal = MataKuliah::where(function ($q) {
                    $q->whereRaw("LOWER(nama) LIKE '%magang%'")
                        ->orWhereRaw("LOWER(nama) LIKE '%tugas akhir%'")
                        ->orWhereRaw("LOWER(nama) LIKE '%proyek keamanan%'");
                })->get()->unique('nama');

                foreach ($kelasTarget->get() as $k) {
                    $namaKelasLower = strtolower($k->nama);
                    preg_match('/\d/', $k->nama, $matches);
                    $tingkatAngka = isset($matches[0]) ? (int) $matches[0] : 0;
                    $insertedMbkm = [];

                    foreach ($mbkmGlobal as $mGlob) {
                        $namaMatkulLower = strtolower($mGlob->nama);
                        $matchRks3 = str_contains($namaKelasLower, 'rks') && $tingkatAngka === 3 && (str_contains($namaMatkulLower, 'magang') || str_contains($namaMatkulLower, 'proyek keamanan'));
                        $matchRks4 = str_contains($namaKelasLower, 'rks') && $tingkatAngka === 4 && (str_contains($namaMatkulLower, 'tugas akhir') || str_contains($namaMatkulLower, 'akhir'));
                        $matchTi3 = str_contains($namaKelasLower, 'ti') && $tingkatAngka === 3 && (str_contains($namaMatkulLower, 'tugas akhir') || str_contains($namaMatkulLower, 'akhir'));

                        if (($matchRks3 || $matchRks4 || $matchTi3) && !in_array($namaMatkulLower, $insertedMbkm, true)) {
                            $matkulMandiri->push(['nama_matkul' => $mGlob->nama, 'nama_dosen' => 'Mandiri', 'kelas' => $k->nama]);
                            $insertedMbkm[] = $namaMatkulLower;
                        }
                    }
                }
            }
        }

        return [
            'daftarTahunAjar' => $daftarTahunAjar,
            'targetTahunAjarId' => $targetTahunAjarId,
            'dosens' => Dosen::whereIn('id', Jadwal::whereIn('tahun_ajar_id', $activeTahunAjarIds)->distinct()->pluck('dosen_id'))->orderBy('nama')->get(),
            'kelas' => Kelas::orderBy('nama')->get(),
            'ruangs' => Ruang::orderBy('nama')->get(),
            'prodis' => ProgramStudi::orderBy('nama')->get(),
            'matrixJadwal' => $matrixJadwal,
            'matkulMandiri' => $matkulMandiri,
            'totalSesi' => $totalSesi,
            'hariKerja' => $hariKerja,
            'filterLabels' => $this->filterLabels($request, $targetTahunAjarId),
            'tampilPerKelas' => !$request->filled('dosen_id') && !$request->filled('kelas_id') && !$request->filled('ruang_id'),
        ];
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

    private function getWarna($id): string
    {
        $colors = ['bg-pink-200', 'bg-blue-200', 'bg-yellow-200', 'bg-green-200', 'bg-purple-200', 'bg-teal-200', 'bg-red-200'];

        return $colors[$id % count($colors)];
    }
}
