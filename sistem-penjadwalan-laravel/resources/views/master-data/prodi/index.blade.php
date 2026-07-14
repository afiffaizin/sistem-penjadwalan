@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-6xl">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Master Data: Program Studi</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola data program studi yang terdaftar dalam sistem.</p>
        </div>
        <a href="{{ route('prodi.create') }}" class="bg-amber-500 text-white px-5 py-2.5 rounded-lg shadow-sm shadow-amber-200 hover:bg-amber-600 font-medium transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Tambah Prodi
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center">
            <i class="fa-solid fa-circle-check mr-3 text-green-500"></i> {{ session('success') }}
        </div>
    @endif

    @php
        // Mapping Singkatan ke Nama Panjang Program Studi
        $namaPanjangProdi = [
            'RKS'  => 'Rekayasa Keamanan Siber',
            'TI'   => 'Teknik Informatika',
            'TRM'  => 'Teknologi Rekayasa Multimedia',
            'TRPL' => 'Teknologi Rekayasa Perangkat Lunak'
        ];
    @endphp

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                        <th class="px-6 py-4 w-16 text-center font-bold">No</th>
                        <th class="px-6 py-4 font-bold">Kode Prodi</th>
                        <th class="px-6 py-4 font-bold">Nama Program Studi</th>
                        <th class="px-6 py-4 text-center w-32 font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($prodis as $index => $p)
                        @php
                            // Ambil kode prodi, ubah ke huruf kapital, lalu bersihkan spasi
                            $kodeClean = strtoupper(trim($p->kode ?? $p->nama)); 
                            // Cari nama panjangnya, kalau tidak terdaftar pakai nama asli dari database
                            $displayNama = $namaPanjangProdi[$kodeClean] ?? $p->nama;
                        @endphp
                        <tr class="hover:bg-amber-50/50 transition">
                            <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $index + 1 }}</td>
                            
                            {{-- Kolom Kode Prodi (Menggunakan property $p->kode asli database) --}}
                            <td class="px-6 py-4 font-bold text-amber-600">{{ strtoupper($p->kode ?? $p->nama) }}</td>
                            
                            {{-- Kolom Nama Program Studi Berhasil Diterjemahkan Panjang --}}
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $displayNama }}</td>
                            
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-3">
                                    <a href="{{ route('prodi.edit', $p->id) }}" class="text-blue-500 hover:text-blue-700 transition" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('prodi.destroy', $p->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus prodi ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection