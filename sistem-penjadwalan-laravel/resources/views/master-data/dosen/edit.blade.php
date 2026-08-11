@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b border-gray-100 pb-4">Edit Data Dosen</h2>

        <form action="{{ route('dosen.update', $dosen->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Kode Dosen</label>
                <input type="text" name="kode_dosen" value="{{ old('kode_dosen', $dosen->kode_dosen) }}" 
                    class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 px-4 py-2.5 outline-none transition" required>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                <input type="text" name="nama" value="{{ old('nama', $dosen->nama) }}" 
                    class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 px-4 py-2.5 outline-none transition" required>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">NIP</label>
                <input type="text" name="nip" value="{{ old('nip', $dosen->nip) }}" 
                    class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 px-4 py-2.5 outline-none transition">
            </div>

            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-2">Tahun Ajar <span class="text-red-500">*</span></label>
                <select name="tahun_ajar_id" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 px-4 py-2.5 outline-none transition bg-white @error('tahun_ajar_id') border-red-500 @enderror" required>
                    <option value="">-- Pilih Tahun Ajar --</option>
                    @foreach($tahunAjars as $ta)
                        <option value="{{ $ta->id }}" {{ old('tahun_ajar_id', $dosen->tahun_ajar_id) == $ta->id ? 'selected' : '' }}>
                            {{ $ta->tahun }} - {{ ucfirst($ta->semester) }} @if($ta->is_active) (AKTIF) @endif
                        </option>
                    @endforeach
                </select>
                @error('tahun_ajar_id') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('dosen.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-bold transition">Batal</a>
                <button type="submit" class="px-5 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-bold shadow-md transition">Update Dosen</button>
            </div>
        </form>
    </div>
</div>
@endsection