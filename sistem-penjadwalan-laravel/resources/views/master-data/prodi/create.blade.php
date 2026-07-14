@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b border-gray-100 pb-4">Tambah Program Studi Baru</h2>

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('prodi.store') }}" method="POST">
            @csrf
            
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Kode Program Studi</label>
                <input type="text" name="kode" value="{{ old('kode') }}" 
                    class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 px-4 py-2.5 outline-none transition @error('kode') border-red-500 @enderror" 
                    placeholder="Contoh: TI / RKS" required>
                @error('kode') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
            </div>

            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Program Studi</label>
                <input type="text" name="nama" value="{{ old('nama') }}" 
                    class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 px-4 py-2.5 outline-none transition @error('nama') border-red-500 @enderror" 
                    placeholder="Contoh: Teknik Informatika" required>
                @error('nama') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('prodi.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-bold transition">Batal</a>
                <button type="submit" class="px-5 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 font-bold shadow-md shadow-amber-200 transition">Simpan Prodi</button>
            </div>
        </form>
    </div>
</div>
@endsection
