@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-2xl p-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-black text-gray-800">Tambah Pengguna Baru</h3>
            <p class="text-gray-500 text-xs mt-1">Daftarkan akun resmi untuk fungsionaris jurusan/prodi.</p>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Lengkap</label>
                <input type="text" name="nama" value="{{ old('nama') }}" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm" required placeholder="Contoh: Dr. Budi Santoso, M.T.">
                @error('nama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Username (Untuk Login)</label>
                <input type="text" name="username" value="{{ old('username') }}" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm" required placeholder="Contoh: budi_santoso">
                @error('username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm" required placeholder="Contoh: budi@kampus.ac.id">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Password Awal</label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm" required placeholder="Minimal 8 karakter">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jabatan / Role</label>
                <select name="role" id="roleSelect" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white" required onchange="toggleProdiSelect()">
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="kajur" {{ old('role') == 'kajur' ? 'selected' : '' }}>Ketua Jurusan (Kajur)</option>
                    <option value="kaprodi" {{ old('role') == 'kaprodi' ? 'selected' : '' }}>Ketua Prodi (Kaprodi)</option>
                </select>
                @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div id="prodiWrapper" class="{{ old('role') == 'kaprodi' ? '' : 'hidden' }}">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Program Studi Terkait</label>
                <select name="prodi_id" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white">
                    <option value="">-- Pilih Program Studi --</option>
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}" {{ old('prodi_id') == $prodi->id ? 'selected' : '' }}>{{ $prodi->nama }}</option>
                    @endforeach
                </select>
                @error('prodi_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('users.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-lg text-sm font-bold hover:bg-gray-200 transition">Batal</a>
                <button type="submit" class="px-6 py-2.5 bg-amber-600 text-white rounded-lg text-sm font-bold hover:bg-amber-700 transition shadow-sm">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleProdiSelect() {
        const roleSelect = document.getElementById('roleSelect');
        const prodiWrapper = document.getElementById('prodiWrapper');
        
        if (roleSelect.value === 'kaprodi') {
            prodiWrapper.classList.remove('hidden');
        } else {
            prodiWrapper.classList.add('hidden');
        }
    }
</script>
@endsection