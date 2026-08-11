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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode_dosen', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $dosens = $query->paginate(10);

        return view('master-data.dosen.index', compact('dosens', 'tahunAjars'));
    }

    public function create()
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'asc')->get();

        return view('master-data.dosen.create', compact('tahunAjars'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_dosen'    => 'required|unique:dosens,kode_dosen',
            'nama'          => 'required',
            'nip'           => 'nullable|string',
            'tahun_ajar_id' => 'required|exists:tahun_ajars,id',
        ]);

        Dosen::create($request->only(['kode_dosen', 'nama', 'nip', 'tahun_ajar_id']));

        return redirect()->route('dosen.index')->with('success', 'Data Dosen berhasil ditambahkan.');
    }

    public function edit(Dosen $dosen)
    {
        $tahunAjars = TahunAjar::orderBy('tahun', 'desc')->orderBy('semester', 'asc')->get();

        return view('master-data.dosen.edit', compact('dosen', 'tahunAjars'));
    }

    public function update(Request $request, Dosen $dosen)
    {
        $request->validate([
            'kode_dosen'    => 'required|unique:dosens,kode_dosen,' . $dosen->id,
            'nama'          => 'required',
            'nip'           => 'nullable|string',
            'tahun_ajar_id' => 'required|exists:tahun_ajars,id',
        ]);

        $dosen->update($request->only(['kode_dosen', 'nama', 'nip', 'tahun_ajar_id']));

        return redirect()->route('dosen.index')->with('success', 'Data Dosen berhasil diperbarui.');
    }

    public function destroy(Dosen $dosen)
    {
        if ($dosen->unavailableDays()->exists() || \App\Models\DosenMatkul::where('dosen_id', $dosen->id)->exists() || \App\Models\Jadwal::where('dosen_id', $dosen->id)->exists()) {
            return back()->with('error', 'Gagal menghapus: Dosen ini masih memiliki data terkait (jadwal/ploting/hari tidak mengajar). Hapus data terkait terlebih dahulu.');
        }

        $dosen->delete();
        return back()->with('success', 'Data Dosen berhasil dihapus.');
    }
}
