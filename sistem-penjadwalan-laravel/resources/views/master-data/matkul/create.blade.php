@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b border-gray-100 pb-4">Tambah Mata Kuliah</h2>

        <form action="{{ route('matkul.store') }}" method="POST">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Mata Kuliah</label>
                <input type="text" name="nama" value="{{ old('nama') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none transition focus:border-amber-500 focus:ring-amber-500" placeholder="Contoh: Pemrograman Web">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">SKS Teori</label>
                    <input type="number" name="sks_teori" value="{{ old('sks_teori', 0) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none transition focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">SKS Praktikum</label>
                    <input type="number" name="sks_praktikum" value="{{ old('sks_praktikum', 0) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none transition focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">SKS Total</label>
                    <input type="number" name="sks_total" value="{{ old('sks_total', 0) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none transition focus:border-amber-500 focus:ring-amber-500 bg-amber-50" placeholder="Total">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Program Studi</label>
                    <select name="prodi_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none bg-white transition focus:border-amber-500 focus:ring-amber-500">
                        <option value="">-- Pilih Prodi --</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}">{{ $prodi->kode }}  {{ $prodi->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Kode Group (Opsional)</label>
                    <input type="text" name="kode_group" value="{{ old('kode_group') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none transition focus:border-amber-500 focus:ring-amber-500" placeholder="Contoh: MB-01">
                    <p class="text-xs text-gray-500 mt-1">Gunakan kode yang sama untuk menghubungkan Teori dan Praktikum</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('matkul.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-bold transition">Batal</a>
                <button type="submit" class="px-5 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 font-bold shadow-md transition">Simpan Matkul</button>
            </div>
        </form>
    </div>
</div>
@endsection