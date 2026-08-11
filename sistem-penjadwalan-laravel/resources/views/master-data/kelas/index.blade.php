@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-6xl">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Master Data: Kelas</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola data rombongan belajar mahasiswa per program studi.</p>
        </div>
        <a href="{{ route('kelas.create') }}" class="bg-amber-500 text-white px-5 py-2.5 rounded-lg shadow-sm shadow-amber-200 hover:bg-amber-600 font-medium transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Tambah Kelas
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center">
            <i class="fa-solid fa-circle-check mr-3 text-green-500"></i> {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('kelas.index') }}" class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full">
                <div class="relative w-full sm:w-64 md:w-72">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-3 py-2 rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500" placeholder="Cari nama kelas...">
                </div>
                
                <label class="text-sm font-bold text-gray-700 whitespace-nowrap hidden md:block">Tahun Akademik:</label>
                <select name="tahun_ajar_id" class="w-full sm:w-56 rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
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
                    @if(request('tahun_ajar_id') || request('search'))
                        <a href="{{ route('kelas.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 font-medium text-sm transition flex items-center gap-2">
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
                        <th class="px-6 py-4 w-16 text-center font-bold">No</th>
                        <th class="px-6 py-4 font-bold">Nama Kelas</th>
                        <th class="px-6 py-4 font-bold">Program Studi</th>
                        <th class="px-6 py-4 font-bold">Tahun Ajar</th>
                        <th class="px-6 py-4 text-center w-32 font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($kelasList as $index => $k)
                        <tr class="hover:bg-amber-50/50 transition">
                            <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $kelasList->firstItem() + $index }}</td>
                            <td class="px-6 py-4 font-bold text-gray-800">{{ $k->nama }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded-md border border-indigo-100 text-xs font-semibold">
                                    {{ $k->prodi->nama ?? 'Tidak Diketahui' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $k->tahun_ajar->tahun ?? '-' }} ({{ $k->tahun_ajar->semester ?? '-' }})
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-3">
                                    <a href="{{ route('kelas.edit', $k->id) }}" class="text-blue-500 hover:text-blue-700 transition" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('kelas.destroy', $k->id) }}" method="POST" class="inline-block delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="text-red-500 hover:text-red-700 transition btn-delete" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-gray-500">
                                <i class="fa-solid fa-users text-4xl text-gray-300 mb-3 block"></i>
                                Belum ada data Kelas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $kelasList->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const form = this.closest('.delete-form');
                
                Swal.fire({
                    title: 'Yakin hapus data ini?',
                    text: 'Data yang dihapus tidak dapat dikembalikan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush