<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\ProgramStudi;
use App\Models\TahunAjar;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index()
    {
        $kelasList = Kelas::with(['prodi', 'tahun_ajar'])->orderBy('nama', 'asc')->get();
        return view('master-data.kelas.index', compact('kelasList'));
    }

    public function create()
    {
        $prodis = ProgramStudi::orderBy('nama', 'asc')->get();
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->get();
        return view('master-data.kelas.create', compact('prodis', 'tahunAjars'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:50',
            'prodi_id' => 'required|exists:program_studis,id',
            'tahun_ajar_id' => 'required|exists:tahun_ajars,id',
        ]);

        $exists = Kelas::where('nama', $request->nama)
            ->where('prodi_id', $request->prodi_id)
            ->where('tahun_ajar_id', $request->tahun_ajar_id)
            ->first();

        if ($exists) {
            return back()->withInput()->with('error', 'Kelas dengan nama ini sudah ada di prodi dan tahun ajar tersebut.');
        }

        Kelas::create($request->all());

        return redirect()->route('kelas.index')->with('success', 'Data Kelas berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $kelas = Kelas::findOrFail($id);
        $prodis = ProgramStudi::orderBy('nama', 'asc')->get();
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->get();
        return view('master-data.kelas.edit', compact('kelas', 'prodis', 'tahunAjars'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:50',
            'prodi_id' => 'required|exists:program_studis,id',
            'tahun_ajar_id' => 'required|exists:tahun_ajars,id',
        ]);

        $kelas = Kelas::findOrFail($id);

        $exists = Kelas::where('nama', $request->nama)
            ->where('prodi_id', $request->prodi_id)
            ->where('tahun_ajar_id', $request->tahun_ajar_id)
            ->where('id', '!=', $id)
            ->first();

        if ($exists) {
            return back()->withInput()->with('error', 'Kelas dengan nama ini sudah ada di prodi dan tahun ajar tersebut.');
        }

        $kelas->update($request->all());

        return redirect()->route('kelas.index')->with('success', 'Data Kelas berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->delete();

        return redirect()->route('kelas.index')->with('success', 'Data Kelas berhasil dihapus!');
    }
}
