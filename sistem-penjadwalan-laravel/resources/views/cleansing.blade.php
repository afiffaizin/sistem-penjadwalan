@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Status Cleansing Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="mb-8 border-b border-gray-100 pb-6">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Status Cleansing Data</h1>
            <p class="text-gray-500 text-sm mt-2">Validasi inkonsistensi relasi Dosen, Mata Kuliah, dan Ruangan.</p>
        </div>

        @if(session('success'))
            <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6 border border-green-200 flex items-center">
                <i class="fa-solid fa-circle-check text-xl mr-3"></i> <strong>Berhasil! </strong> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200 flex items-center">
                <i class="fa-solid fa-circle-exclamation text-xl mr-3"></i> <strong>Gagal! </strong> {{ session('error') }}
            </div>
        @endif

        @if($isCleansed)
            <div>
                <div class="flex justify-between items-end mb-4 px-2">
                    <h3 class="font-bold text-gray-800 text-lg">Hasil Analisis Sistem</h3>
                    <span class="text-xs font-semibold text-gray-500"><i class="fa-regular fa-clock mr-1"></i> 3 Komponen Tervalidasi</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="bg-white border border-amber-200 rounded-xl p-5 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-lg flex items-center justify-center text-lg"><i class="fa-regular fa-user"></i></div>
                            <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2.5 py-1 rounded-full"><i class="fa-solid fa-check"></i> 100% Valid</span>
                        </div>
                        <h4 class="font-bold text-gray-800">Dosen & Pengampu</h4>
                    </div>

                    <div class="bg-white border border-amber-200 rounded-xl p-5 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-10 h-10 bg-indigo-50 text-amber-500 rounded-lg flex items-center justify-center text-lg"><i class="fa-solid fa-book-open"></i></div>
                            <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2.5 py-1 rounded-full"><i class="fa-solid fa-check"></i> 100% Valid</span>
                        </div>
                        <h4 class="font-bold text-gray-800">Mata Kuliah & SKS</h4>
                    </div>

                    <div class="bg-white border border-amber-200 rounded-xl p-5 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-lg flex items-center justify-center text-lg"><i class="fa-regular fa-building"></i></div>
                            <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2.5 py-1 rounded-full"><i class="fa-solid fa-check"></i> 100% Valid</span>
                        </div>
                        <h4 class="font-bold text-gray-800">Kapasitas Ruang</h4>
                    </div>
                </div>

                <form action="{{ route('cleansing.store') }}" method="POST" class="flex justify-end pt-4 border-t border-gray-100">
                    @csrf
                    <button type="submit" class="px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg shadow-md transition flex items-center gap-2">
                        Simpan ke Database & Lanjut Generate <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>

        @else
            <div class="border-2 border-dashed border-gray-200 bg-gray-50 rounded-2xl p-10 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 bg-white border border-amber-100 rounded-full flex items-center justify-center mb-5 text-amber-500 text-2xl"><i class="fa-solid fa-ban"></i></div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Data Belum Tersedia</h2>
                <p class="text-gray-500 text-sm max-w-md mx-auto mb-6">Anda belum melakukan upload data. Silakan kembali ke halaman Upload untuk memasukkan file Excel terlebih dahulu.</p>
                <a href="{{ route('upload.form') }}" class="px-6 py-2.5 bg-amber-500 text-white font-bold rounded-full shadow-md">Ke Halaman Upload</a>
            </div>
        @endif
    </div>

    {{-- Import History Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="mb-6 border-b border-gray-100 pb-4">
            <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                <div class="bg-amber-100 text-amber-600 w-10 h-10 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                </div>
                Riwayat Import Data
            </h2>
            <p class="text-gray-500 text-sm mt-2">Daftar seluruh data yang pernah diimport ke sistem.</p>
        </div>

        @if($importHistories->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tahun Ajaran</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Semester</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Import</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($importHistories as $index => $history)
                        <tr class="hover:bg-amber-50/50 transition" id="row-{{ $history->id }}">
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $history->tahun }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $history->semester === 'Gasal' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ $history->semester }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <i class="fa-regular fa-calendar mr-1"></i> {{ $history->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($history->is_active)
                                    <span class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold"><i class="fa-solid fa-circle-check mr-1"></i>Aktif</span>
                                @else
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-bold">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button"
                                    onclick="resetImport({{ $history->id }}, '{{ $history->tahun }} - {{ $history->semester }}')"
                                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center gap-1.5 mx-auto">
                                    <i class="fa-solid fa-rotate-left"></i> Reset
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-10 text-center">
                <i class="fa-solid fa-inbox text-5xl text-gray-200 mb-4 block"></i>
                <h3 class="text-lg font-bold text-gray-700 mb-1">Belum Ada Riwayat Import</h3>
                <p class="text-gray-500 text-sm">Data akan muncul di sini setelah Anda melakukan upload dan menyimpan data ke database.</p>
            </div>
        @endif
    </div>

</div>

{{-- Hidden form for reset --}}
<form id="formResetImport" action="{{ route('cleansing.reset') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="tahun_ajar_id" id="resetTahunAjarId">
</form>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function resetImport(id, label) {
        Swal.fire({
            title: 'Reset Data Import?',
            html: `Anda akan menghapus <strong>seluruh data</strong> untuk:<br><span class="text-red-600 font-bold text-lg">${label}</span><br><br>Termasuk: Jadwal, Pengampu, Kelas, Dosen, Mata Kuliah, dan Ruang yang dibuat oleh import ini.<br><br>Tindakan ini <strong>tidak dapat dibatalkan</strong>. Lanjutkan?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: '<i class="fa-solid fa-rotate-left mr-1"></i> Ya, Reset Sekarang!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Mereset Data...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                document.getElementById('resetTahunAjarId').value = id;
                document.getElementById('formResetImport').submit();
            }
        });
    }
</script>
@endpush
@endsection
