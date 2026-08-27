@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-7xl">
    <div class="bg-gradient-to-r from-amber-700 to-amber-900 text-white p-8 rounded-2xl shadow-md mb-8">
        <h1 class="text-3xl font-black mb-1">Pilih Hari Tidak Bisa Mengajar</h1>
        <p class="text-amber-200 text-sm font-medium">Atur request hari tidak bisa mengajar untuk dosen di Prodi {{ $prodi->nama }}. Perubahan pada dosen yang mengajar di beberapa prodi akan berlaku secara global.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
            <ul class="list-disc list-inside text-sm font-semibold">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <form method="GET" action="{{ route('kaprodi.unavailable-days') }}" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-80">
                <label for="tahun_ajar_id" class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Tahun Ajar</label>
                <select id="tahun_ajar_id" name="tahun_ajar_id" class="w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                    @foreach($tahunAjars as $ta)
                        <option value="{{ $ta->id }}" {{ (string) $selectedTahunAjarId === (string) $ta->id ? 'selected' : '' }}>
                            {{ $ta->tahun }} - {{ $ta->semester }} {{ $ta->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold transition">
                Tampilkan
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('kaprodi.unavailable-days.store') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @csrf
        <input type="hidden" name="tahun_ajar_id" value="{{ $selectedTahunAjarId }}">

        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="text-xl font-black text-gray-800">Daftar Dosen Prodi</h2>
                <p class="text-sm text-gray-500 font-medium">Centang hari ketika dosen tidak bisa mengajar.</p>
            </div>
            <button type="submit" class="px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-black transition shadow-md shadow-amber-200/50">
                Simpan Request
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4 text-left font-black">Dosen</th>
                        @foreach($hariKerja as $hari)
                            <th class="px-6 py-4 text-center font-black">{{ $hari }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dosens as $dosen)
                        <tr class="hover:bg-amber-50/40 transition">
                            <td class="px-6 py-4">
                                <div class="font-black text-gray-800">{{ $dosen->nama }}</div>
                                <div class="text-xs text-gray-400 font-semibold">{{ $dosen->nip ?: ($dosen->kode_dosen ?: '-') }}</div>
                            </td>
                            @foreach($hariKerja as $hari)
                                <td class="px-6 py-4 text-center">
                                    <input
                                        type="checkbox"
                                        name="hari[{{ $dosen->id }}][]"
                                        value="{{ $hari }}"
                                        class="rounded border-gray-300 text-amber-500 focus:ring-amber-500"
                                        {{ in_array($hari, $requests[$dosen->id] ?? [], true) ? 'checked' : '' }}
                                    >
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($hariKerja) + 1 }}" class="px-6 py-10 text-center text-gray-500 font-semibold">
                                Belum ada dosen terhubung dengan prodi ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
</div>
@endsection
