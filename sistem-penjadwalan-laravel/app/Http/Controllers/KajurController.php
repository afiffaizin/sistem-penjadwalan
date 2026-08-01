<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Ruang;
use App\Models\TahunAjar;
use App\Services\JadwalViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KajurController extends Controller
{
    public function dashboard(Request $request)
    {
        if (auth()->user()->role !== 'kajur') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

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

        // 1. Hitung Statistik
        $totalDosen = Jadwal::where($queryTahun)
            ->distinct('dosen_id')
            ->count('dosen_id');

        $totalKelas = Kelas::where($queryTahun)->count();
        $totalRuang = Ruang::where($queryTahun)->count();

        // 2. Data Beban Mengajar Per Prodi (aggregate query)
        $bebanProdiRaw = DB::table('jadwals')
            ->join('kelas', 'jadwals.kelas_id', '=', 'kelas.id')
            ->join('mata_kuliahs', 'jadwals.mata_kuliah_id', '=', 'mata_kuliahs.id')
            ->join('program_studis', 'kelas.prodi_id', '=', 'program_studis.id')
            ->when($selectedTahunAjarId, fn ($q) => $q->where('jadwals.tahun_ajar_id', $selectedTahunAjarId))
            ->groupBy('program_studis.id', 'program_studis.nama')
            ->selectRaw('program_studis.id, program_studis.nama, COALESCE(SUM(mata_kuliahs.sks_total), 0) as total_sks')
            ->get();

        $semuaProdi = ProgramStudi::all();
        $bebanProdiMap = $semuaProdi->mapWithKeys(fn ($p) => [$p->id => ['nama' => $p->nama, 'total_sks' => 0]])->toArray();
        foreach ($bebanProdiRaw as $row) {
            $bebanProdiMap[$row->id] = ['nama' => $row->nama, 'total_sks' => (int) $row->total_sks];
        }
        $bebanProdi = array_values($bebanProdiMap);

        // Data Kepadatan Jadwal Per Hari (aggregate query)
        $kepadatanRaw = DB::table('jadwals')
            ->when($selectedTahunAjarId, fn ($q) => $q->where('tahun_ajar_id', $selectedTahunAjarId))
            ->groupBy('hari')
            ->selectRaw('hari, COUNT(*) as total')
            ->pluck('total', 'hari');

        $jadwalPerHariMap = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];
        foreach ($kepadatanRaw as $hari => $total) {
            if (isset($jadwalPerHariMap[$hari])) {
                $jadwalPerHariMap[$hari] = (int) $total;
            }
        }

        $kepadatanHari = [
            'label' => array_keys($jadwalPerHariMap),
            'data'  => array_values($jadwalPerHariMap) 
        ];
        
        return view('kajur.dashboard', compact('totalDosen', 'totalKelas', 'totalRuang', 'bebanProdi', 'kepadatanHari', 'tahunAjars', 'selectedTahunAjarId'));
    }

    public function lihatJadwal(Request $request, JadwalViewService $jadwalViewService)
    {
        if (auth()->user()->role !== 'kajur') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return view('kajur.lihat-jadwal', $jadwalViewService->buildPublic($request));
    }
}
