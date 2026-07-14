@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b border-gray-100 pb-4">Edit Data Ruangan</h2>

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('ruang.update', $ruang->id) }}" method="POST">
            @csrf
            @method('PUT')
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Ruangan</label>
                    <input type="text" name="nama" value="{{ old('nama', $ruang->nama) }}" 
                        class="mb-4 w-full border border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 px-4 py-2.5 outline-none transition @error('nama') border-red-500 @enderror" required>
                    @error('nama') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Kategori</label>
                    <select name="kategori" class="mb-4 w-full border border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 px-4 py-2.5 outline-none bg-white transition @error('kategori') border-red-500 @enderror">
                        <option value="Teori" {{ old('kategori', $ruang->kategori) == 'Teori' ? 'selected' : '' }}>Teori</option>
                        <option value="Praktikum" {{ old('kategori', $ruang->kategori) == 'Praktikum' ? 'selected' : '' }}>Praktikum</option>
                    </select>
                    @error('kategori') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-2">Program Studi Pemilik</label>
                <select name="prodi_id" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 px-4 py-2.5 outline-none bg-white transition @error('prodi_id') border-red-500 @enderror">
                    <option value="">-- Pilih Program Studi --</option>
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}" {{ old('prodi_id', $ruang->prodi_id) == $prodi->id ? 'selected' : '' }}>
                            {{ $prodi->kode }}  {{ $prodi->nama }}
                        </option>
                    @endforeach
                </select>
                @error('prodi_id') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('ruang.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-bold transition">Batal</a>
                <button type="submit" class="px-5 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-bold shadow-md transition">Update Ruangan</button>
            </div>
        </form>
    </div>
</div>
@endsection