<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\TahunAjar;
use Illuminate\Http\Request;

class DosenController extends Controller
{
    public function index(Request $request)
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'asc')->get();

        $query = Dosen::with('tahunAjar')->orderBy('nama', 'asc');

        if ($request->filled('tahun_ajar_id')) {
            $query->where('tahun_ajar_id', $request->tahun_ajar_id);
        }

        $dosens = $query->paginate(10);

        return view('master-data.dosen.index', compact('dosens', 'tahunAjars'));
    }

    public function create()
    {
        return view('master-data.dosen.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_dosen' => 'required|unique:dosens,kode_dosen',
            'nama'       => 'required',
            'nip'       => 'nullable|string'
        ]);

        Dosen::create($request->all());
        return redirect()->route('dosen.index')->with('success', 'Data Dosen berhasil ditambahkan.');
    }

    public function edit(Dosen $dosen)
    {
        return view('master-data.dosen.edit', compact('dosen'));
    }

    public function update(Request $request, Dosen $dosen)
    {
        $request->validate([
            'kode_dosen' => 'required|unique:dosens,kode_dosen,' . $dosen->id,
            'nama'       => 'required',
            'nip'       => 'nullable|string'
        ]);

        $dosen->update($request->all());
        return redirect()->route('dosen.index')->with('success', 'Data Dosen berhasil diperbarui.');
    }

    public function destroy(Dosen $dosen)
    {
        $dosen->delete();
        return back()->with('success', 'Data Dosen berhasil dihapus.');
    }
}
