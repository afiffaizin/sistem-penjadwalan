@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-6xl">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Master Data: Ruangan</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola data lokasi ruangan perkuliahan.</p>
        </div>
        <a href="{{ route('ruang.create') }}" class="bg-amber-500 text-white px-5 py-2.5 rounded-lg shadow-sm shadow-amber-200 hover:bg-amber-600 font-medium transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Tambah Ruangan
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center">
            <i class="fa-solid fa-circle-check mr-3 text-green-500"></i> {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                        <th class="px-6 py-4 font-bold text-center w-16">No</th>
                        <th class="px-6 py-4 font-bold">Nama Ruangan</th>
                        <th class="px-6 py-4 font-bold">Prodi</th>
                        <th class="px-6 py-4 font-bold text-center w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ruangList as $index => $r)
                        <tr class="hover:bg-amber-50/50 transition">
                            <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 font-bold text-gray-800">
                                {{ $r->nama }} <br>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded {{ strtolower($r->kategori) == 'teori' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ strtoupper($r->kategori) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $r->prodi->nama ?? 'Umum' }}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-3">
                                    <a href="{{ route('ruang.edit', $r->id) }}" class="text-blue-500 hover:text-blue-700 transition"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <form action="{{ route('ruang.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Yakin hapus?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-10 text-gray-500">Belum ada data Ruangan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection