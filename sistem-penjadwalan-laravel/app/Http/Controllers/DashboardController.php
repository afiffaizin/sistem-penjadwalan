<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\DosenMatkul;
use App\Models\MataKuliah;
use App\Models\Ruang;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
    
        if ($user->role === 'sekretaris') {
            return redirect()->route('sekjur.dashboard'); 
        }

        if ($user->role === 'kajur') {
            return redirect()->route('kajur.dashboard'); 
        }

        if ($user->role === 'kaprodi') {
            return redirect()->route('kaprodi.dashboard'); 
        }

        return redirect('/');
    }
    
    public function dashboardSekjur(Request $request)
    {
        $tahunAjars = \App\Models\TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        
        // Default to the latest generated schedule, fallback to active
        $latestJadwalTaId = \App\Models\Jadwal::latest('created_at')->value('tahun_ajar_id');
        $activeTahunAjar = \App\Models\TahunAjar::where('is_active', true)->first();
        $defaultTahunAjarId = $latestJadwalTaId ?? $activeTahunAjar?->id;
        
        $selectedTahunAjarId = $request->input('tahun_ajar_id', $defaultTahunAjarId);

        $queryTahun = function ($q) use ($selectedTahunAjarId) {
            if ($selectedTahunAjarId) {
                $q->where('tahun_ajar_id', $selectedTahunAjarId);
            }
        };

        $jumlahDosen = Dosen::where($queryTahun)->count();
        $jumlahMatkul = MataKuliah::where($queryTahun)->count();
        $jumlahRuangan = Ruang::where($queryTahun)->count();

        $jumlahTeori = MataKuliah::where($queryTahun)->where('sks_teori', '>', 0)->where('sks_praktikum', 0)->count();
        $jumlahPraktikum = MataKuliah::where($queryTahun)->where('sks_praktikum', '>', 0)->where('sks_teori', 0)->count();
        $jumlahCampuran = MataKuliah::where($queryTahun)->where('sks_teori', '>', 0)->where('sks_praktikum', '>', 0)->count();

        // 2. DATA CHART Top 5 Beban Mengajar Dosen
        $semuaDosen = Dosen::where($queryTahun)->get()->keyBy('id');
        $semuaMatkul = MataKuliah::where($queryTahun)->get()->keyBy('id');
        
        $dosenMatkulsQuery = DosenMatkul::query();
        if ($selectedTahunAjarId) {
            $dosenMatkulsQuery->where('tahun_ajar_id', $selectedTahunAjarId);
        }
        $dosenMatkuls = $dosenMatkulsQuery->get();

        $topDosen = $dosenMatkuls->groupBy('dosen_id')->map(function ($items, $dosenId) use ($semuaDosen, $semuaMatkul) {
            $namaLengkap = $semuaDosen[$dosenId]->nama ?? 'Dosen Tidak Di temukan';
            $namaPendek = implode(' ', array_slice(explode(' ', $namaLengkap), 0, 2));

            $teori = 0;
            $praktikum = 0;

            foreach ($items as $item) {
                $mk = $semuaMatkul[$item->mata_kuliah_id] ?? null;
                if ($mk) {
                    $teori += (int) $mk->sks_teori;
                    $praktikum += (int) $mk->sks_praktikum;
                }
            }

            return [
                'nama' => $namaPendek,
                'teori' => $teori,
                'praktikum' => $praktikum,
                'total' => $teori + $praktikum
            ];
        })->sortByDesc('total')->take(5)->values();

        $chartDosenLabels = $topDosen->pluck('nama')->toArray();
        $chartDosenTeori = $topDosen->pluck('teori')->toArray();
        $chartDosenPraktikum = $topDosen->pluck('praktikum')->toArray();

        return view('sekjur.dashboard', compact('jumlahDosen', 'jumlahMatkul', 'jumlahRuangan', 'jumlahTeori', 'jumlahPraktikum', 'jumlahCampuran', 'chartDosenLabels', 'chartDosenTeori', 'chartDosenPraktikum', 'tahunAjars', 'selectedTahunAjarId'));
    }
}
