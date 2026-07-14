@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-7xl">
    
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border-l-8 border-amber-500 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight mb-1">Manajemen Pengguna</h1>
            <p class="text-gray-500 text-sm">Tambah atau kelola akun Ketua Jurusan (Kajur) dan Ketua Program Studi (Kaprodi).</p>
        </div>
        <a href="{{ route('users.create') }}" class="px-5 py-2.5 bg-amber-500 text-white px-5 py-2.5 rounded-lg shadow-sm shadow-amber-200 hover:bg-amber-600 font-medium transition flex items-center gap-2">
            <i class="fa-solid fa-user-plus mr-2"></i> Tambah Pengguna
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl mb-6 text-sm font-bold shadow-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-green-500 text-lg"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-2">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-500 uppercase text-[10px] font-extrabold tracking-wider">
                        <th class="px-6 py-4">Nama Lengkap</th>
                        <th class="px-6 py-4">Username / Email</th>
                        <th class="px-6 py-4">Jabatan (Role)</th>
                        <th class="px-6 py-4">Program Studi</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700 text-sm">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50/50 transition duration-150">
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $user->nama }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-start gap-1.5">
                                    <span class="font-semibold text-gray-700 text-sm">{{ $user->email }}</span>
                                    <span class="inline-block bg-white text-gray-500 px-2 py-0.5 text-[10px] font-bold rounded border border-gray-200 shadow-sm">
                                        <i class="fa-solid fa-at opacity-50 mr-0.5"></i>{{ $user->username }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wide {{ $user->role === 'kajur' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' }}">
                                    {{ $user->role === 'kajur' ? 'Ketua Jurusan' : 'Ketua Prodi' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-600 text-sm">
                                {{ $user->prodi->nama ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('users.edit', $user->id) }}" class="flex items-center justify-center w-8 h-8 text-blue-500 hover:text-blue-700 rounded transition" title="Edit Pengguna">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex items-center justify-center w-8 h-8 text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 rounded transition" title="Hapus Pengguna">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-3 border-2 border-gray-100">
                                    <i class="fa-solid fa-users-slash text-2xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-700 mb-1">Belum Ada Pengguna</h3>
                                <p class="text-gray-500 text-sm">Silakan tambahkan data pengguna baru melalui tombol di atas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
</div>
@endsection