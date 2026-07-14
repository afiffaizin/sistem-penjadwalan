@extends('layouts.app')

@section('content')
  <div class="container mx-auto p-4 max-w-xl">
      <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
          <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2">Edit Program Studi</h2>

          <form action="{{ route('prodi.update', $prodi->id) }}" method="POST">
              @csrf
              @method('PUT')
              
              <div class="mb-4">
                  <label class="block text-sm font-bold text-gray-700 mb-2">Kode Prodi</label>
                  <input type="text" name="kode" value="{{ old('kode', $prodi->kode) }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring-amber-500 @error('kode') border-red-500 @enderror">
                  @error('kode') <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p> @enderror
              </div>

              <div class="mb-4">
                  <label class="block text-sm font-bold text-gray-700 mb-2">Nama Program Studi</label>
                  <input type="text" name="nama" value="{{ old('nama', $prodi->nama) }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring-amber-500 @error('nama') border-red-500 @enderror">
                  @error('nama') <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p> @enderror
              </div>

              <div class="flex justify-end gap-3 mt-8">
                  <a href="{{ route('prodi.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 font-medium">Batal</a>
                  <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 font-medium">Update Data</button>
              </div>
          </form>
      </div>
  </div>
@endsection