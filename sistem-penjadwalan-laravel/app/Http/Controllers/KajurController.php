<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Ruang;
use App\Models\TahunAjar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KajurController extends Controller
{
    public function dashboard()
    {
        if (auth()->user()->role !== 'kajur') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $activeTahunAjarIds = TahunAjar::where('is_active', true)->pluck('id')->toArray();

        // 1. Hitung Statistik
        $totalDosen = Jadwal::whereIn('tahun_ajar_id', $activeTahunAjarIds)
            ->distinct('dosen_id')
            ->count('dosen_id');

        $totalKelas = Kelas::count();
        $totalRuang = Ruang::count();

        // 2. Data Beban Mengajar Per Prodi
        $bebanProdiMap = [];
        $semuaProdi = ProgramStudi::all();
        foreach ($semuaProdi as $p) {
            $bebanProdiMap[$p->id] = [
                'nama' => $p->nama,
                'total_sks' => 0
            ];
        }

        $jadwals = Jadwal::with(['kelas', 'mata_kuliah'])
            ->whereIn('tahun_ajar_id', $activeTahunAjarIds)
            ->get();

        // Kelompokkan dan jumlahkan SKS ke masing-masing Prodi
        foreach ($jadwals as $jdwl) {
            if ($jdwl->kelas && $jdwl->mata_kuliah) {
                $prodiId = $jdwl->kelas->prodi_id; 

                if (isset($bebanProdiMap[$prodiId])) {
                    $bebanProdiMap[$prodiId]['total_sks'] += $jdwl->mata_kuliah->sks_total ?? 0;
                }
            }
        }

        $bebanProdi = array_values($bebanProdiMap);

        // Data Kepadatan Jadwal Per Hari
        $jadwalPerHariMap = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];

        foreach ($jadwals as $jdwl) {
            if (isset($jadwalPerHariMap[$jdwl->hari])) {
                $jadwalPerHariMap[$jdwl->hari]++;
            }
        }

        $kepadatanHari = [
            'label' => array_keys($jadwalPerHariMap),
            'data'  => array_values($jadwalPerHariMap) 
        ];
        
        return view('kajur.dashboard', compact('totalDosen', 'totalKelas', 'totalRuang', 'bebanProdi', 'kepadatanHari'));
    }

    public function lihatJadwal(Request $request)
    {
        if (auth()->user()->role !== 'kajur') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $activeTahunAjarIds = TahunAjar::where('is_active', true)->pluck('id')->toArray();
        $dosenIds = Jadwal::whereIn('tahun_ajar_id', $activeTahunAjarIds)->distinct()->pluck('dosen_id');
        $dosens = Dosen::whereIn('id', $dosenIds)->orderBy('nama')->get();
        $kelas  = Kelas::orderBy('nama')->get();
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

        // --- LOGIKA QUERY DATA JADWAL ---
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

            // Mapping ke Matriks
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

            // Query MBKM
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

        return view('kajur.lihat-jadwal', compact(
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
        $colors = ['bg-pink-200', 'bg-blue-200', 'bg-yellow-200', 'bg-green-200', 'bg-purple-200', 'bg-teal-200', 'bg-red-200', 'bg-blue-50'];
        return $colors[$matkulId % count($colors)];
    }
}
