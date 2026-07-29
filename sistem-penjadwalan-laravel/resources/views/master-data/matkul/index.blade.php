@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-6xl">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Master Data: Mata Kuliah</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola kurikulum dan mata kuliah per program studi.</p>
        </div>
        <a href="{{ route('matkul.create') }}" class="bg-amber-500 text-white px-5 py-2.5 rounded-lg shadow-sm shadow-amber-200 hover:bg-amber-600 font-medium transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Tambah Matkul
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center">
            <i class="fa-solid fa-circle-check mr-3 text-green-500"></i> {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('matkul.index') }}" class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full md:w-auto">
                <label class="text-sm font-bold text-gray-700 whitespace-nowrap">Filter Tahun Akademik:</label>
                <select name="tahun_ajar_id" class="w-full sm:w-64 rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">Semua Tahun Akademik</option>
                    @foreach($tahunAjars as $ta)
                        <option value="{{ $ta->id }}" {{ (string) request('tahun_ajar_id') === (string) $ta->id ? 'selected' : '' }}>
                            {{ $ta->tahun }} ({{ ucfirst($ta->semester) }}) {{ $ta->is_active ? ' - [Aktif]' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2">
                    <button type="submit" class="bg-amber-500 text-white px-4 py-2 rounded-lg shadow-sm shadow-amber-200 hover:bg-amber-600 font-medium text-sm transition flex items-center gap-2">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    @if(request('tahun_ajar_id'))
                        <a href="{{ route('matkul.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 font-medium text-sm transition flex items-center gap-2">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                        <th class="px-6 py-4 font-bold">Mata Kuliah</th>
                        <th class="px-6 py-4 font-bold text-center">SKS (T/P/Total)</th>
                        <th class="px-6 py-4 font-bold text-center">Group</th>
                        <th class="px-6 py-4 font-bold text-center">Prodi</th>
                        <th class="px-6 py-4 font-bold">Tahun Ajar</th>
                        <th class="px-6 py-4 font-bold text-center w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($matkulList as $index => $m)
                        <tr class="hover:bg-amber-50/50 transition">
                            <td class="px-6 py-4 font-bold text-gray-800">{{ $matkulList->firstItem() + $index }}. {{ $m->nama }}</td>
                            <td class="px-6 py-4 text-center font-medium text-gray-700">
                                {{ $m->sks_teori }} / {{ $m->sks_praktikum }} / <strong class="text-amber-600">{{ $m->sks_total }}</strong>
                            </td>
                            <td class="px-6 py-4 text-center font-medium text-gray-600 text-sm">
                                @if($m->kode_group)
                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded-md border border-blue-100 text-xs font-semibold">{{ $m->kode_group }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-center text-gray-600">
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded-md border border-indigo-100 text-xs font-semibold">
                                    {{ $m->prodi->nama ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $m->tahunAjar ? $m->tahunAjar->tahun . ' (' . $m->tahunAjar->semester . ')' : 'Master Umum' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-3">
                                    <a href="{{ route('matkul.edit', $m->id) }}" class="text-blue-500 hover:text-blue-700 transition"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <form action="{{ route('matkul.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Yakin hapus?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-10 text-gray-500">Belum ada data Mata Kuliah.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $matkulList->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection