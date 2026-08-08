<?php

namespace App\Http\Controllers;

use App\Models\Ruang;
use App\Models\ProgramStudi;
use App\Models\TahunAjar;
use Illuminate\Http\Request;

class RuangController extends Controller
{
    public function index(Request $request)
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'asc')->get();

        $query = Ruang::with(['prodi', 'tahunAjar'])->orderBy('nama', 'asc');

        if ($request->filled('tahun_ajar_id')) {
            $query->where('tahun_ajar_id', $request->tahun_ajar_id);
        }

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        $ruangList = $query->paginate(10);

        return view('master-data.ruang.index', compact('ruangList', 'tahunAjars'));
    }

    public function create()
    {
        $prodis = ProgramStudi::orderBy('nama', 'asc')->get();
        return view('master-data.ruang.create', compact('prodis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'      => 'required',
            'kategori'  => 'required',
            'prodi_id'  => 'required|exists:program_studis,id'
        ]);

        Ruang::create($request->only(['nama', 'kategori', 'prodi_id', 'tahun_ajar_id']));
        return redirect()->route('ruang.index')->with('success', 'Data Ruangan berhasil ditambahkan.');
    }

    public function edit(Ruang $ruang)
    {
        $prodis = ProgramStudi::orderBy('nama', 'asc')->get();
        return view('master-data.ruang.edit', compact('ruang', 'prodis'));
    }

    public function update(Request $request, Ruang $ruang)
    {
        $request->validate([
            'nama'      => 'required',
            'kategori'  => 'required',
            'prodi_id'  => 'required|exists:program_studis,id'
        ]);

        $ruang->update($request->only(['nama', 'kategori', 'prodi_id', 'tahun_ajar_id']));
        return redirect()->route('ruang.index')->with('success', 'Data Ruangan berhasil diperbarui.');
    }

    public function destroy(Ruang $ruang)
    {
        if (\App\Models\Jadwal::where('ruang_id', $ruang->id)->exists()) {
            return back()->with('error', 'Gagal menghapus: Ruangan ini masih digunakan dalam jadwal.');
        }

        $ruang->delete();
        return back()->with('success', 'Data Ruangan berhasil dihapus.');
    }
}
