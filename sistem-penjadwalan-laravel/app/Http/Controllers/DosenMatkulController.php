<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\DosenMatkul;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\TahunAjar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DosenMatkulController extends Controller
{
    public function index(Request $request)
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();

        $query = DosenMatkul::with(['dosen', 'mata_kuliah', 'kelas', 'tahun_ajar']);

        if ($request->filled('tahun_ajar_id')) {
            $query->where('tahun_ajar_id', $request->tahun_ajar_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('dosen', function($q2) use ($search) {
                    $q2->where('nama', 'like', "%{$search}%");
                })->orWhereHas('mata_kuliah', function($q2) use ($search) {
                    $q2->where('nama', 'like', "%{$search}%");
                })->orWhereHas('kelas', function($q2) use ($search) {
                    $q2->where('nama', 'like', "%{$search}%");
                });
            });
        }

        $plottings = $query->paginate(20)->withQueryString();

        return view('master-data.plotting.index', compact('plottings', 'tahunAjars'));
    }

    public function create(Request $request)
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        $dosens = Dosen::orderBy('nama', 'asc')->get();
        $matkuls = MataKuliah::orderBy('nama', 'asc')->get();
        
        $activeTa = TahunAjar::where('is_active', true)->first();
        $kelasQuery = Kelas::orderBy('nama', 'asc');
        if ($activeTa) {
            $kelasQuery->where('tahun_ajar_id', $activeTa->id);
        }
        $kelas = $kelasQuery->get();

        return view('master-data.plotting.create', compact('tahunAjars', 'dosens', 'matkuls', 'kelas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun_ajar_id'  => 'required|exists:tahun_ajars,id',
            'dosen_id'       => 'required',
            'mata_kuliah_id' => 'required',
            'kelas_id'       => 'required',
        ]);

        $taId = $request->tahun_ajar_id;

        // Auto-Create Dosen jika input bukan ID numerik
        $dosenId = $request->dosen_id;
        if (!is_numeric($dosenId)) {
            $dosen = Dosen::firstOrCreate(
                ['nama' => $dosenId],
                [
                    'kode_dosen' => 'D-' . strtoupper(Str::random(5)),
                    'nip' => null
                ]
            );
            $dosenId = $dosen->id;
        }

        // Auto-Create Program Studi Umum jika dibutuhkan
        $defaultProdi = ProgramStudi::firstOrCreate(['nama' => 'Umum']);

        // Auto-Create Mata Kuliah jika input bukan ID numerik
        $matkulId = $request->mata_kuliah_id;
        if (!is_numeric($matkulId)) {
            $sksTeori = (int) $request->input('sks_teori', 0);
            $sksPraktikum = (int) $request->input('sks_praktikum', 0);

            $matkul = MataKuliah::firstOrCreate(
                ['nama' => $matkulId, 'prodi_id' => $defaultProdi->id],
                [
                    'sks_teori' => $sksTeori,
                    'sks_praktikum' => $sksPraktikum,
                    'sks_total' => $sksTeori + $sksPraktikum,
                    'kode_group' => null
                ]
            );
            $matkulId = $matkul->id;
        }

        // Auto-Create Kelas jika input bukan ID numerik
        $kelasId = $request->kelas_id;
        if (!is_numeric($kelasId)) {
            $kls = Kelas::firstOrCreate(
                ['nama' => $kelasId, 'prodi_id' => $defaultProdi->id, 'tahun_ajar_id' => $taId]
            );
            $kelasId = $kls->id;
        }

        // Simpan Relasi (Cek duplikasi agar tidak double insert)
        DosenMatkul::firstOrCreate([
            'dosen_id' => $dosenId,
            'mata_kuliah_id' => $matkulId,
            'kelas_id' => $kelasId,
            'tahun_ajar_id' => $taId
        ]);

        return redirect()->route('dosen-matkul.index')->with('success', 'Data Plotting berhasil ditambahkan.');
    }

    public function edit(DosenMatkul $dosen_matkul)
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'desc')->get();
        $dosens = Dosen::orderBy('nama', 'asc')->get();
        $matkuls = MataKuliah::orderBy('nama', 'asc')->get();
        
        $activeTa = TahunAjar::where('is_active', true)->first();
        $kelasQuery = Kelas::orderBy('nama', 'asc');
        if ($activeTa) {
            $kelasQuery->where('tahun_ajar_id', $activeTa->id);
        }
        $kelas = $kelasQuery->get();

        return view('master-data.plotting.edit', compact('dosen_matkul', 'tahunAjars', 'dosens', 'matkuls', 'kelas'));
    }

    public function update(Request $request, DosenMatkul $dosen_matkul)
    {
        $request->validate([
            'tahun_ajar_id'  => 'required|exists:tahun_ajars,id',
            'dosen_id'       => 'required',
            'mata_kuliah_id' => 'required',
            'kelas_id'       => 'required',
        ]);

        $taId = $request->tahun_ajar_id;

        // Auto-Create Dosen
        $dosenId = $request->dosen_id;
        if (!is_numeric($dosenId)) {
            $dosen = Dosen::firstOrCreate(
                ['nama' => $dosenId],
                ['kode_dosen' => 'D-' . strtoupper(Str::random(5))]
            );
            $dosenId = $dosen->id;
        }

        $defaultProdi = ProgramStudi::firstOrCreate(['nama' => 'Umum']);

        // Auto-Create Mata Kuliah
        $matkulId = $request->mata_kuliah_id;
        if (!is_numeric($matkulId)) {
            $sksTeori = (int) $request->input('sks_teori', 0);
            $sksPraktikum = (int) $request->input('sks_praktikum', 0);

            $matkul = MataKuliah::firstOrCreate(
                ['nama' => $matkulId, 'prodi_id' => $defaultProdi->id],
                ['sks_teori' => $sksTeori, 'sks_praktikum' => $sksPraktikum, 'sks_total' => $sksTeori + $sksPraktikum]
            );
            $matkulId = $matkul->id;
        }

        // Auto-Create Kelas
        $kelasId = $request->kelas_id;
        if (!is_numeric($kelasId)) {
            $kls = Kelas::firstOrCreate(
                ['nama' => $kelasId, 'prodi_id' => $defaultProdi->id, 'tahun_ajar_id' => $taId]
            );
            $kelasId = $kls->id;
        }

        $dosen_matkul->update([
            'dosen_id' => $dosenId,
            'mata_kuliah_id' => $matkulId,
            'kelas_id' => $kelasId,
            'tahun_ajar_id' => $taId
        ]);

        return redirect()->route('dosen-matkul.index')->with('success', 'Data Plotting berhasil diperbarui.');
    }

    public function destroy(DosenMatkul $dosen_matkul)
    {
        $dosen_matkul->delete();
        return back()->with('success', 'Data Plotting berhasil dihapus.');
    }
}
