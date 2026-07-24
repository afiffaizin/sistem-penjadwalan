<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\DosenUnavailableDay;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Ruang;
use App\Models\TahunAjar;
use Illuminate\Http\Request;

class KaprodiController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        if ($user->role !== 'kaprodi') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        if (!$user->prodi_id) {
            return "Akun Anda belum dihubungkan dengan Program Studi manapun. Hubungi Sekretaris Jurusan.";
        }

        $prodiId = $user->prodi_id;
        $prodi = ProgramStudi::find($prodiId);
        $activeTahunAjarIds = TahunAjar::where('is_active', true)->pluck('id')->toArray();

        $totalKelas = Kelas::where('prodi_id', $prodiId)->count();
        $kelasIds = Kelas::where('prodi_id', $prodiId)->pluck('id')->toArray();

        $jadwals = Jadwal::with(['mata_kuliah', 'ruang'])
            ->whereIn('kelas_id', $kelasIds)
            ->whereIn('tahun_ajar_id', $activeTahunAjarIds)
            ->get();

        $totalDosen = $jadwals->pluck('dosen_id')->unique()->count();

        $totalSks = 0;

        $jadwalPerHariMap = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];
        $kategoriKuliah = ['Teori' => 0, 'Praktikum' => 0];

        foreach ($jadwals as $jdwl) {
            if ($jdwl->mata_kuliah) {
                $totalSks += $jdwl->mata_kuliah->sks_total ?? 0;
            }

            // Hitung Kepadatan Per Hari
            if (isset($jadwalPerHariMap[$jdwl->hari])) {
                $jadwalPerHariMap[$jdwl->hari]++;
            }

            // Hitung Proporsi Teori vs Praktikum berdasarkan kategori ruangan
            if ($jdwl->ruang) {
                $kat = strtolower($jdwl->ruang->kategori ?? 'teori');
                if (str_contains($kat, 'lab') || str_contains($kat, 'praktikum') || str_contains($kat, 'bengkel')) {
                    $kategoriKuliah['Praktikum']++;
                } else {
                    $kategoriKuliah['Teori']++;
                }
            }
        }

        $kepadatanHari = [
            'label' => array_keys($jadwalPerHariMap),
            'data'  => array_values($jadwalPerHariMap)
        ];

        $jenisKuliah = [
            'label' => array_keys($kategoriKuliah),
            'data'  => array_values($kategoriKuliah)
        ];

        return view('kaprodi.dashboard', compact(
            'prodi',
            'totalKelas',
            'totalDosen',
            'totalSks',
            'kepadatanHari',
            'jenisKuliah'
        ));
    }

    public function lihatJadwal(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'kaprodi' || !$user->prodi_id) {
            abort(403, 'Akses Ditolak.');
        }

        $prodiId = $user->prodi_id;
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        $activeTahunAjar = TahunAjar::where('is_active', true)->first();
        $selectedTahunAjarId = $request->input('tahun_ajar_id', $activeTahunAjar?->id);

        $kelasQuery = Kelas::where('prodi_id', $prodiId);
        if ($selectedTahunAjarId) {
            $kelasQuery->where('tahun_ajar_id', $selectedTahunAjarId);
        }
        $kelas = $kelasQuery->orderBy('nama')->get();
        $kelasIds = $kelas->pluck('id')->toArray();

        $dosenQuery = Jadwal::whereIn('kelas_id', $kelasIds);
        if ($selectedTahunAjarId) {
            $dosenQuery->where('tahun_ajar_id', $selectedTahunAjarId);
        } else {
            $dosenQuery->whereIn('tahun_ajar_id', TahunAjar::where('is_active', true)->pluck('id')->toArray());
        }
        $dosenIds = $dosenQuery->distinct()->pluck('dosen_id');

        $dosens = Dosen::whereIn('id', $dosenIds)->orderBy('nama')->get();
        $ruangs = Ruang::orderBy('nama')->get();

        $hariKerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $totalSesi = 8;
        $matrixJadwal = [];

        for ($s = 1; $s <= $totalSesi; $s++) {
            foreach ($hariKerja as $hari) {
                $matrixJadwal[$s][$hari] = [];
            }
        }

        // query matkul mbkm
        $matkulMandiri = collect();
        $mbkmGlobal = MataKuliah::where(function ($q) {
            $q->whereRaw("LOWER(nama) LIKE '%magang%'")
                ->orWhereRaw("LOWER(nama) LIKE '%tugas akhir%'")
                ->orWhereRaw("LOWER(nama) LIKE '%proyek keamanan%'");
        })->get()->unique('nama');

        foreach ($kelas as $k) {
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

        $query = Jadwal::with(['mata_kuliah', 'dosen', 'kelas', 'ruang'])
            ->whereIn('kelas_id', $kelasIds); 

        if ($selectedTahunAjarId) {
            $query->where('tahun_ajar_id', $selectedTahunAjarId);
        } else {
            $query->whereIn('tahun_ajar_id', TahunAjar::where('is_active', true)->pluck('id')->toArray());
        }

        if ($request->filled('dosen_id')) $query->where('dosen_id', $request->dosen_id);
        if ($request->filled('kelas_id')) $query->where('kelas_id', $request->kelas_id);
        if ($request->filled('ruang_id')) $query->where('ruang_id', $request->ruang_id);

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

        return view('kaprodi.lihat-jadwal', compact(
            'dosens',
            'kelas',
            'ruangs',
            'matrixJadwal',
            'matkulMandiri',
            'totalSesi',
            'hariKerja',
            'tahunAjars',
            'selectedTahunAjarId'
        ));
    }

    public function unavailableDays(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'kaprodi' || !$user->prodi_id) {
            abort(403, 'Akses Ditolak.');
        }

        $hariKerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $prodi = ProgramStudi::findOrFail($user->prodi_id);
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        $activeTahunAjar = TahunAjar::where('is_active', true)->first();
        $selectedTahunAjarId = $request->input('tahun_ajar_id', $activeTahunAjar?->id);

        $dosens = Dosen::whereHas('prodis', function ($query) use ($user) {
                $query->where('program_studis.id', $user->prodi_id);
            })
            ->orderBy('nama')
            ->get();

        $requests = DosenUnavailableDay::with(['dosen', 'tahunAjar'])
            ->where('prodi_id', $user->prodi_id)
            ->when($selectedTahunAjarId, function ($query) use ($selectedTahunAjarId) {
                $query->where('tahun_ajar_id', $selectedTahunAjarId);
            })
            ->get()
            ->groupBy('dosen_id')
            ->map(function ($items) {
                return $items->pluck('hari')->toArray();
            })
            ->toArray();

        return view('kaprodi.unavailable-days', compact(
            'hariKerja',
            'prodi',
            'tahunAjars',
            'selectedTahunAjarId',
            'dosens',
            'requests'
        ));
    }

    public function storeUnavailableDays(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'kaprodi' || !$user->prodi_id) {
            abort(403, 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'tahun_ajar_id' => ['required', 'exists:tahun_ajars,id'],
            'hari' => ['nullable', 'array'],
            'hari.*' => ['array'],
            'hari.*.*' => ['in:Senin,Selasa,Rabu,Kamis,Jumat'],
        ]);

        $tahunAjarId = $validated['tahun_ajar_id'];
        $hariByDosen = $validated['hari'] ?? [];
        $allowedDosenIds = Dosen::whereHas('prodis', function ($query) use ($user) {
                $query->where('program_studis.id', $user->prodi_id);
            })
            ->pluck('id')
            ->toArray();

        DosenUnavailableDay::where('prodi_id', $user->prodi_id)
            ->where('tahun_ajar_id', $tahunAjarId)
            ->whereIn('dosen_id', $allowedDosenIds)
            ->delete();

        foreach ($hariByDosen as $dosenId => $hariList) {
            if (!in_array((int) $dosenId, $allowedDosenIds, true)) {
                continue;
            }

            foreach (array_unique($hariList) as $hari) {
                DosenUnavailableDay::create([
                    'user_id' => $user->id,
                    'dosen_id' => (int) $dosenId,
                    'prodi_id' => $user->prodi_id,
                    'tahun_ajar_id' => $tahunAjarId,
                    'hari' => $hari,
                ]);
            }
        }

        return redirect()
            ->route('kaprodi.unavailable-days', ['tahun_ajar_id' => $tahunAjarId])
            ->with('success', 'Request hari tidak bisa mengajar berhasil disimpan.');
    }

    public function monitorUnavailableDays(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'sekretaris') {
            abort(403, 'Akses Ditolak.');
        }

        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        $prodis = ProgramStudi::orderBy('nama')->get();
        $dosens = Dosen::orderBy('nama')->get();

        $requests = DosenUnavailableDay::with(['user', 'dosen', 'prodi', 'tahunAjar'])
            ->when($request->filled('tahun_ajar_id'), function ($query) use ($request) {
                $query->where('tahun_ajar_id', $request->tahun_ajar_id);
            })
            ->when($request->filled('prodi_id'), function ($query) use ($request) {
                $query->where('prodi_id', $request->prodi_id);
            })
            ->when($request->filled('dosen_id'), function ($query) use ($request) {
                $query->where('dosen_id', $request->dosen_id);
            })
            ->orderBy('tahun_ajar_id', 'desc')
            ->orderBy('prodi_id')
            ->orderBy('dosen_id')
            ->get();

        return view('sekjur.unavailable-days', compact('tahunAjars', 'prodis', 'dosens', 'requests'));
    }

    private function getWarna($matkulId)
    {
        $colors = ['bg-pink-200', 'bg-blue-200', 'bg-yellow-200', 'bg-green-200', 'bg-purple-200', 'bg-teal-200', 'bg-red-200', 'bg-blue-50'];
        return $colors[$matkulId % count($colors)];
    }
}
