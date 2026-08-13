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
use App\Services\JadwalViewService;
use Illuminate\Http\Request;

class KaprodiController extends Controller
{
    public function dashboard(Request $request)
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
        
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        
        $latestJadwalTaId = Jadwal::latest('created_at')->value('tahun_ajar_id');
        $activeTahunAjar = TahunAjar::where('is_active', true)->first();
        $defaultTahunAjarId = $latestJadwalTaId ?? $activeTahunAjar?->id;
        
        $selectedTahunAjarId = $request->input('tahun_ajar_id', $defaultTahunAjarId);

        $queryTahun = function ($q) use ($selectedTahunAjarId) {
            if ($selectedTahunAjarId) {
                $q->where('tahun_ajar_id', $selectedTahunAjarId);
            }
        };

        $totalKelas = Kelas::where('prodi_id', $prodiId)->where($queryTahun)->count();
        $kelasIds = Kelas::where('prodi_id', $prodiId)->where($queryTahun)->pluck('id')->toArray();

        $jadwals = Jadwal::with(['mata_kuliah', 'ruang'])
            ->whereIn('kelas_id', $kelasIds)
            ->where($queryTahun)
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
            'jenisKuliah',
            'tahunAjars',
            'selectedTahunAjarId'
        ));
    }

    public function lihatJadwal(Request $request, JadwalViewService $jadwalViewService)
    {
        $user = auth()->user();

        if ($user->role !== 'kaprodi' || !$user->prodi_id) {
            abort(403, 'Akses Ditolak.');
        }

        return view('kaprodi.lihat-jadwal', $jadwalViewService->buildForProdi($request, $user->prodi_id));
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
        
        $latestJadwalTaId = Jadwal::latest('created_at')->value('tahun_ajar_id');
        $activeTahunAjar = TahunAjar::where('is_active', true)->first();
        $defaultTahunAjarId = $latestJadwalTaId ?? $activeTahunAjar?->id;
        
        $selectedTahunAjarId = $request->input('tahun_ajar_id', $defaultTahunAjarId);

        $dosens = Dosen::where('homebase_prodi_id', $user->prodi_id)
            ->orderBy('nama')
            ->get();

        $dosenIds = $dosens->pluck('id')->toArray();

        $requests = DosenUnavailableDay::with(['dosen', 'tahunAjar'])
            ->whereIn('dosen_id', $dosenIds)
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
        $allowedDosenIds = Dosen::where('homebase_prodi_id', $user->prodi_id)
            ->pluck('id')
            ->toArray();

        DosenUnavailableDay::where('tahun_ajar_id', $tahunAjarId)
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
                $query->where(function ($q) use ($request) {
                    $q->where('prodi_id', $request->prodi_id)
                      ->orWhereHas('dosen', function ($q2) use ($request) {
                          $q2->where('homebase_prodi_id', $request->prodi_id);
                      });
                });
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

}
