<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TahunAjar;
use App\Models\Jadwal;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Ruang;
use App\Models\ProgramStudi;
use App\Models\MataKuliah;

class PublicController extends Controller
{
    public function index(Request $request)
    {
        $activeTahunAjarIds = TahunAjar::where('is_active', true)->pluck('id')->toArray();

        $dosenIds = Jadwal::whereIn('tahun_ajar_id', $activeTahunAjarIds)->distinct()->pluck('dosen_id');
        $dosens = Dosen::whereIn('id', $dosenIds)->orderBy('nama')->get();
        $kelas  = Kelas::whereIn('tahun_ajar_id', $activeTahunAjarIds)->orderBy('nama')->get();
        $ruangs = Ruang::orderBy('nama')->get();
        $prodis = ProgramStudi::orderBy('nama')->get();

        $hariKerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $totalSesi = 8;
        $matrixJadwal = [];

        for ($s = 1; $s <= $totalSesi; $s++) {
            foreach ($hariKerja as $hari) {
                $matrixJadwal[$s][$hari] = [];
            }
        }

        $matkulMandiri = collect();

        // filter jadwal
        if ($request->anyFilled(['dosen_id', 'kelas_id', 'ruang_id', 'prodi_id'])) {
            $query = Jadwal::with(['mata_kuliah', 'dosen', 'kelas', 'ruang'])
                ->whereIn('tahun_ajar_id', $activeTahunAjarIds);

            if ($request->filled('dosen_id')) $query->where('dosen_id', $request->dosen_id);
            if ($request->filled('kelas_id')) $query->where('kelas_id', $request->kelas_id);
            if ($request->filled('ruang_id')) $query->where('ruang_id', $request->ruang_id);

            if ($request->filled('prodi_id')) {
                $query->whereHas('kelas', function ($q) use ($request) {
                    $q->where('prodi_id', $request->prodi_id);
                });
            }

            $jadwals = $query->get();

            foreach ($jadwals as $j) {
                for ($s = $j->sesi_mulai; $s <= $j->sesi_selesai; $s++) {
                    if ($s <= $totalSesi) {
                        $matrixJadwal[$s][$j->hari][] = [
                            'id'           => $j->id,
                            'sesi_mulai'   => $j->sesi_mulai,
                            'sesi_selesai' => $j->sesi_selesai,
                            'hari'         => $j->hari,
                            'mata_kuliah'  => $j->mata_kuliah->nama ?? '-',
                            'dosen'       => $j->dosen->nama ?? '-',
                            'kelas'       => $j->kelas->nama ?? '-',
                            'ruang'       => $j->ruang->nama ?? '-',
                            'jenis'       => isset($j->ruang->kategori) ? ucfirst($j->ruang->kategori) : '-',
                            'warna'       => $this->getWarna($j->mata_kuliah_id ?? 0)
                        ];
                    }
                }
            }

            // QUERY MBKM / MANDIRI
            if ($request->filled('kelas_id') || $request->filled('prodi_id')) {

                $kelasTarget = Kelas::query();
                if ($request->filled('kelas_id')) $kelasTarget->where('id', $request->kelas_id);
                if ($request->filled('prodi_id')) $kelasTarget->where('prodi_id', $request->prodi_id);
                $kelasDitemukan = $kelasTarget->get();

                $mbkmGlobal = MataKuliah::where(function ($q) {
                    $q->whereRaw("LOWER(nama) LIKE '%magang%'")
                        ->orWhereRaw("LOWER(nama) LIKE '%tugas akhir%'")
                        ->orWhereRaw("LOWER(nama) LIKE '%proyek keamanan%'");
                })->get()->unique('nama');

                foreach ($kelasDitemukan as $k) {
                    $namaKelasLower = strtolower($k->nama);
                    preg_match('/\d/', $k->nama, $matches);
                    $tingkatAngka = isset($matches[0]) ? (int)$matches[0] : 0;
                    $insertedMbkm = [];

                    foreach ($mbkmGlobal as $mGlob) {
                        $namaMatkulLower = strtolower($mGlob->nama);

                        if (str_contains($namaKelasLower, 'rks')) {
                            if ($tingkatAngka == 3 && (str_contains($namaMatkulLower, 'magang') || str_contains($namaMatkulLower, 'proyek keamanan'))) {
                                if (!in_array($namaMatkulLower, $insertedMbkm)) {
                                    $matkulMandiri->push(['nama_matkul' => $mGlob->nama, 'nama_dosen' => 'Mandiri', 'kelas' => $k->nama]);
                                    $insertedMbkm[] = $namaMatkulLower;
                                }
                            }
                            if ($tingkatAngka == 4 && (str_contains($namaMatkulLower, 'tugas akhir') || str_contains($namaMatkulLower, 'akhir'))) {
                                if (!in_array($namaMatkulLower, $insertedMbkm)) {
                                    $matkulMandiri->push(['nama_matkul' => $mGlob->nama, 'nama_dosen' => 'Mandiri', 'kelas' => $k->nama]);
                                    $insertedMbkm[] = $namaMatkulLower;
                                }
                            }
                        } elseif (str_contains($namaKelasLower, 'ti')) {
                            if ($tingkatAngka == 3 && (str_contains($namaMatkulLower, 'tugas akhir') || str_contains($namaMatkulLower, 'akhir'))) {
                                if (!in_array($namaMatkulLower, $insertedMbkm)) {
                                    $matkulMandiri->push(['nama_matkul' => $mGlob->nama, 'nama_dosen' => 'Mandiri', 'kelas' => $k->nama]);
                                    $insertedMbkm[] = $namaMatkulLower;
                                }
                            }
                        }
                    }
                }
            }
        }

        return view('welcome', compact(
            'dosens',
            'kelas',
            'ruangs',
            'prodis',
            'matrixJadwal',
            'matkulMandiri',
            'totalSesi',
            'hariKerja'
        ));
    }

    private function getWarna($matkulId)
    {
        $colors = [
            'bg-pink-200',
            'bg-blue-200',
            'bg-yellow-200',
            'bg-green-200',
            'bg-purple-200',
            'bg-teal-200',
            'bg-red-200',
            'bg-blue-50'
        ];
        return $colors[$matkulId % count($colors)];
    }
}
