<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use Illuminate\Http\Request;

class ProdiController extends Controller
{
    public function index()
    {
        $prodis = ProgramStudi::orderBy('nama', 'asc')->get();
        return view('master-data.prodi.index', compact('prodis'));
    }

    public function create()
    {
        return view('master-data.prodi.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:50|unique:program_studis,kode',
            'nama' => 'required|string|max:255',
        ], [
            'kode.required' => 'Kode prodi wajib diisi.',
            'kode.unique' => 'Kode prodi sudah terdaftar.',
            'nama.required' => 'Nama prodi wajib diisi.',
        ]);

        ProgramStudi::create([
            'kode' => $request->kode,
            'nama' => $request->nama,
        ]);

        return redirect()->route('prodi.index')->with('success', 'Data Prodi berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $prodi = ProgramStudi::findOrFail($id);
        return view('master-data.prodi.edit', compact('prodi'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode' => 'required|string|max:50|unique:program_studis,kode,' . $id,
            'nama' => 'required|string|max:255',
        ]);

        $prodi = ProgramStudi::findOrFail($id);
        $prodi->update([
            'kode' => $request->kode,
            'nama' => $request->nama,
        ]);

        return redirect()->route('prodi.index')->with('success', 'Data Prodi berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $prodi = ProgramStudi::findOrFail($id);

        if ($prodi->dosens()->exists() || $prodi->mata_kuliahs()->exists() || $prodi->ruangs()->exists()) {
            return redirect()->route('prodi.index')->with('error', 'Gagal menghapus: Prodi ini masih memiliki data dosen, mata kuliah, atau ruangan terkait.');
        }

        $prodi->delete();

        return redirect()->route('prodi.index')->with('success', 'Data Prodi berhasil dihapus!');
    }
}
