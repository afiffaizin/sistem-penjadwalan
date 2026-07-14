@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-2xl">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-1.5 h-6 bg-amber-500 rounded-full"></div>
            <div>
                <h3 class="text-xl font-extrabold text-gray-900 tracking-tight">Edit Pengguna</h3>
                <p class="text-gray-500 text-xs mt-0.5">Perbarui informasi akun pengguna.</p>
            </div>
        </div>

        <form action="{{ route('users.update', $user->id) }}" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <input type="text" name="nama" value="{{ old('nama', $user->nama) }}" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 text-sm transition" required>
                @error('nama') <p class="text-red-500 text-xs mt-1.5 font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-1.5">Username (Untuk Login)</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 text-sm transition" required>
                @error('username') <p class="text-red-500 text-xs mt-1.5 font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-1.5">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 text-sm transition" required>
                @error('email') <p class="text-red-500 text-xs mt-1.5 font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-1.5">Password Baru <span class="normal-case text-gray-400 font-normal">(Opsional)</span></label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 text-sm transition" placeholder="Kosongkan jika tidak ingin mengubah password">
                @error('password') <p class="text-red-500 text-xs mt-1.5 font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-1.5">Jabatan / Role</label>
                <select name="role" id="roleSelect" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 text-sm bg-white transition" required onchange="toggleProdiSelect()">
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="kajur" {{ old('role', $user->role) == 'kajur' ? 'selected' : '' }}>Ketua Jurusan (Kajur)</option>
                    <option value="kaprodi" {{ old('role', $user->role) == 'kaprodi' ? 'selected' : '' }}>Ketua Prodi (Kaprodi)</option>
                </select>
                @error('role') <p class="text-red-500 text-xs mt-1.5 font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>

            <div id="prodiWrapper" class="{{ old('role', $user->role) == 'kaprodi' ? '' : 'hidden' }}">
                <label class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-1.5">Program Studi Terkait</label>
                <select name="prodi_id" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 text-sm bg-white transition">
                    <option value="">-- Pilih Program Studi --</option>
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}" {{ old('prodi_id', $user->prodi_id) == $prodi->id ? 'selected' : '' }}>{{ $prodi->nama }}</option>
                    @endforeach
                </select>
                @error('prodi_id') <p class="text-red-500 text-xs mt-1.5 font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-5 mt-2 border-t border-gray-100">
                <a href="{{ route('users.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-lg text-sm font-bold hover:bg-gray-200 transition">Batal</a>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 text-white rounded-lg text-sm font-bold hover:bg-amber-600 transition shadow-sm flex items-center">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Perbarui Data
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
            // Reset pilihan jika diganti ke kajur
            prodiWrapper.querySelector('select').value = ''; 
        }
    }
</script>
@endsection