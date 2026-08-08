<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\TahunAjar;
use Illuminate\Http\Request;

class MataKuliahController extends Controller
{
    public function index(Request $request)
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'asc')->get();

        $query = MataKuliah::with(['prodi', 'tahunAjar'])->orderBy('nama', 'asc');

        if ($request->filled('tahun_ajar_id')) {
            $query->where('tahun_ajar_id', $request->tahun_ajar_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode_group', 'like', "%{$search}%");
            });
        }

        $matkulList = $query->paginate(10);

        return view('master-data.matkul.index', compact('matkulList', 'tahunAjars'));
    }

    public function create()
    {
        $prodis = ProgramStudi::orderBy('nama', 'asc')->get();
        return view('master-data.matkul.create', compact('prodis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'          => 'required',
            'sks_teori'     => 'required|numeric',
            'sks_praktikum' => 'required|numeric',
            'sks_total'     => 'required|numeric',
            'kode_group'    => 'nullable|string',
            'prodi_id'      => 'required|exists:program_studis,id'
        ]);

        MataKuliah::create($request->only(['nama', 'sks_teori', 'sks_praktikum', 'sks_total', 'kode_group', 'prodi_id', 'tahun_ajar_id']));
        return redirect()->route('matkul.index')->with('success', 'Data Mata Kuliah berhasil ditambahkan.');
    }

    public function edit(MataKuliah $matkul)
    {
        $prodis = ProgramStudi::orderBy('nama', 'asc')->get();
        return view('master-data.matkul.edit', compact('matkul', 'prodis'));
    }

    public function update(Request $request, MataKuliah $matkul)
    {
        $request->validate([
            'nama'          => 'required',
            'sks_teori'     => 'required|numeric',
            'sks_praktikum' => 'required|numeric',
            'sks_total'     => 'required|numeric',
            'kode_group'    => 'nullable|string',
            'prodi_id'      => 'required|exists:program_studis,id'
        ]);

        $matkul->update($request->only(['nama', 'sks_teori', 'sks_praktikum', 'sks_total', 'kode_group', 'prodi_id', 'tahun_ajar_id']));
        return redirect()->route('matkul.index')->with('success', 'Data Mata Kuliah berhasil diperbarui.');
    }

    public function destroy(MataKuliah $matkul)
    {
        if (\App\Models\DosenMatkul::where('mata_kuliah_id', $matkul->id)->exists() || \App\Models\Jadwal::where('mata_kuliah_id', $matkul->id)->exists()) {
            return back()->with('error', 'Gagal menghapus: Mata kuliah ini masih memiliki data jadwal atau ploting terkait.');
        }

        $matkul->delete();
        return back()->with('success', 'Data Mata Kuliah berhasil dihapus.');
    }
}
