<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('prodi')->where('id', '!=', Auth::id())->orderBy('role')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $prodis = ProgramStudi::orderBy('nama')->get();
        return view('users.create', compact('prodis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:100', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:kajur,kaprodi'],
            'prodi_id' => ['required_if:role,kaprodi', 'nullable', 'exists:program_studis,id'],
        ]);

        User::create([
            'nama' => $request->nama,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'prodi_id' => $request->role === 'kaprodi' ? $request->prodi_id : null, // Kajur tidak terikat prodi
        ]);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $prodis = ProgramStudi::orderBy('nama')->get();

        return view('users.edit', compact('user', 'prodis'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'nama'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role'     => 'required|in:kajur,kaprodi',
            'prodi_id' => 'required_if:role,kaprodi',
        ], [
            'nama.required'         => 'Nama lengkap wajib diisi.',
            'username.required'     => 'Username wajib diisi.',
            'username.unique'       => 'Username sudah digunakan, silakan cari yang lain.',
            'email.required'        => 'Alamat email wajib diisi.',
            'email.unique'          => 'Email sudah terdaftar pada akun lain.',
            'password.min'          => 'Password minimal harus 8 karakter.',
            'role.required'         => 'Jabatan wajib dipilih.',
            'prodi_id.required_if'  => 'Program Studi wajib dipilih jika jabatan adalah Ketua Prodi.',
        ]);

        $dataUpdate = [
            'nama'     => $request->nama,
            'username' => $request->username,
            'email'    => $request->email,
            'role'     => $request->role,
            'prodi_id' => $request->role === 'kajur' ? null : $request->prodi_id,
        ];

        if ($request->filled('password')) {
            $dataUpdate['password'] = bcrypt($request->password);
        }

        $user->update($dataUpdate);
        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus!');
    }
}
