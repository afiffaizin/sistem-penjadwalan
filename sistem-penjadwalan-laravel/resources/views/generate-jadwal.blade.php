@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper .dataTables_length select { padding-right: 2rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #e2e8f0; border-radius: 0.375rem; padding: 0.5rem; outline: none; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #f59e0b; box-shadow: 0 0 0 1px #f59e0b; }
        table.dataTable { border-collapse: collapse !important; }
    </style>

   <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border-l-8 border-amber-500">
            <form id="formGenerate" action="{{ route('jadwal.generate') }}" method="POST" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                @csrf
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 tracking-tight">Generate Jadwal Otomatis</h1>
                    
                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 w-full max-w-md">
                        <label for="tahun_ajar_id" class="text-sm font-bold text-gray-600 whitespace-nowrap">
                            Pilih Tahun Ajar Aktif:
                        </label>
                        <div class="relative w-full">
                            <select name="tahun_ajar_id" id="tahun_ajar_id" onchange="gantiTahunAjar(this.value)" 
                                class="w-full min-w-[240px] bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-amber-500 focus:border-amber-500 block p-2.5 pr-8 font-semibold shadow-sm cursor-pointer appearance-none">
                                @foreach($daftarTahunAjar as $ta)
                                    <option value="{{ $ta->id }}" {{ $ta->id == $tahunAjarAktif->id ? 'selected' : '' }}>
                                        {{ $ta->tahun }} - {{ $ta->semester }} 
                                        @if($ta->is_active) (Aktif Sistem) @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto self-end md:self-center">
                    <button type="button" onclick="mulaiGenerate()" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-xl shadow-md shadow-amber-200 transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-xl"></i> Mulai Auto-Generate
                    </button>
                    @if(isset($jadwalList) && count($jadwalList) > 0)
                        <button type="button" onclick="hapusJadwal()" class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-6 rounded-xl shadow-md shadow-red-200 transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-trash text-lg"></i> Hapus Jadwal
                        </button>
                    @endif
                </div>
            </form>

            {{-- Form tersembunyi khusus untuk aksi DELETE --}}
            <form id="formHapusJadwal" action="{{ route('jadwal.delete') }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
                <input type="hidden" name="tahun_ajar_id" id="hapus_tahun_ajar_id" value="{{ $tahunAjarAktif?->id }}">
            </form>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center mb-6 shadow-sm">
                <i class="fa-solid fa-circle-check mr-3 text-green-500 text-xl"></i>
                <div>
                    <strong class="font-bold">Sukses!</strong> <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center mb-6 shadow-sm">
                <i class="fa-solid fa-circle-exclamation mr-3 text-red-500 text-xl"></i>
                <div>
                    <strong class="font-bold">Error!</strong> <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <div class="bg-amber-100 text-amber-600 w-10 h-10 rounded-lg flex items-center justify-center mr-3">
                    <i class="fa-regular fa-calendar-days text-lg"></i>
                </div>
                Daftar Jadwal Mata Kuliah
            </h2>
            
            <div class="overflow-x-auto">
                @if(count($jadwalList) > 0)
                    <table id="tableJadwal" class="display w-full border-collapse">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hari</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sesi</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kelas</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Mata Kuliah</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Dosen</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($jadwalList as $j)
                            <tr class="hover:bg-amber-50/50 transition">
                                <td class="px-4 py-3 text-sm font-bold text-amber-600">{{ $j->hari }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 font-medium">Sesi {{ $j->sesi_mulai }} - {{ $j->sesi_selesai }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-700 rounded text-xs font-bold border border-gray-200">
                                        {{ $j->kelas->nama ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800 font-semibold">{{ $j->mata_kuliah->nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $j->dosen->nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 font-medium">{{ $j->ruang->nama ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-12 text-center">
                        <i class="fa-solid fa-folder-open text-6xl text-gray-200 mb-4 block"></i>
                        <h3 class="text-lg font-bold text-gray-700 mb-1">Jadwal Belum Digenerate</h3>
                        <p class="text-gray-500">Silakan klik tombol <strong class="text-amber-500">Mulai Auto-Generate</strong> di atas untuk memproses jadwal.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // DataTables hanya akan diinisialisasi jika tabelnya ada
            if ($('#tableJadwal').length) {
                $('#tableJadwal').DataTable({
                    pageLength: 25,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
                });
            }
        });

        // FUNGSI POPUP & LOADING
        function mulaiGenerate() {
            Swal.fire({
                title: 'Mulai Auto-Generate?',
                text: "Proses ini akan memakan waktu hingga beberapa menit dan akan me-reset jadwal lama. Lanjutkan?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '<i class="fa-solid fa-rocket mr-1"></i> Ya, Generate!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    // Munculkan Animasi Loading
                    Swal.fire({
                        title: 'OR-tools Sedang Bekerja...',
                        html: '<span class="text-sm text-gray-500">Sistem sedang mencari kombinasi jadwal terbaik tanpa bentrok.<br><b class="text-red-500">Mohon jangan tutup atau refresh halaman ini!</b></span>',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit form
                    document.getElementById('formGenerate').submit();
                }
            });
        }

        function gantiTahunAjar(id) {
            // Mengarahkan kembali ke halaman index dengan query string parameter tahun_ajar_id
            window.location.href = "{{ route('jadwal.index') }}?tahun_ajar_id=" + id;
        }

        function hapusJadwal() {
            const select = document.getElementById('tahun_ajar_id');
            const labelTahunAjar = select.options[select.selectedIndex].text.trim();

            Swal.fire({
                title: 'Hapus Seluruh Jadwal?',
                html: `Anda akan menghapus <strong>semua data jadwal</strong> untuk:<br><span class="text-red-600 font-bold">${labelTahunAjar}</span><br><br>Tindakan ini <strong>tidak dapat dibatalkan</strong>. Lanjutkan?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '<i class="fa-solid fa-trash mr-1"></i> Ya, Hapus Sekarang!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Sinkronisasi ID tahun ajar ke form hapus
                    document.getElementById('hapus_tahun_ajar_id').value = select.value;

                    Swal.fire({
                        title: 'Menghapus Data...',
                        text: 'Mohon tunggu sebentar.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    document.getElementById('formHapusJadwal').submit();
                }
            });
        }
    </script>
@endsection