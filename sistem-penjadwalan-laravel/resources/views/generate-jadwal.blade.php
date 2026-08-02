@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper .dataTables_length select { padding-right: 2rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #e2e8f0; border-radius: 0.375rem; padding: 0.5rem; outline: none; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #f59e0b; box-shadow: 0 0 0 1px #f59e0b; }
        table.dataTable { border-collapse: collapse !important; }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
        .animate-pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }
    </style>

   <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border-l-8 border-amber-500">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 tracking-tight">Generate Jadwal Otomatis</h1>
                    
                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 w-full max-w-md">
                        <label for="tahun_ajar_id" class="text-sm font-bold text-gray-600 whitespace-nowrap">
                            Pilih Tahun Ajar Aktif:
                        </label>
                        <div class="relative w-full">
                            <select name="tahun_ajar_id" id="tahun_ajar_id" onchange="switchAcademicYear(this.value)" 
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
                    <button type="button" id="btnGenerate" onclick="startGenerate()" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-xl shadow-md shadow-amber-200 transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-xl"></i> Mulai Auto-Generate
                    </button>
                    @if(isset($jadwalList) && count($jadwalList) > 0)
                        <button type="button" id="btnHapus" onclick="deleteSchedule()" class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-6 rounded-xl shadow-md shadow-red-200 transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-trash text-lg"></i> Hapus Jadwal
                        </button>
                    @endif
                </div>
            </div>

            {{-- Status indicator --}}
            <div id="jobStatusBar" class="hidden mt-4 p-4 rounded-lg border">
                <div class="flex items-center gap-3">
                    <div id="statusDot" class="w-3 h-3 rounded-full animate-pulse-dot"></div>
                    <span id="statusText" class="text-sm font-semibold"></span>
                    <span id="statusElapsed" class="text-xs text-gray-400 ml-auto"></span>
                </div>
                <div id="statusError" class="hidden mt-2 text-sm text-red-600 whitespace-pre-line"></div>
            </div>

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
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start mb-6 shadow-sm">
                <i class="fa-solid fa-circle-exclamation mr-3 text-red-500 text-xl mt-0.5"></i>
                <div>{!! session('error') !!}</div>
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
        const CSRF_TOKEN = '{{ csrf_token() }}';
        const URL_GENERATE = '{{ route("jadwal.generate") }}';
        const URL_STATUS = '{{ route("jadwal.generate.status") }}';
        const URL_INDEX = '{{ route("jadwal.index") }}';

        let pollTimer = null;
        let jobStartTime = null;
        let elapsedTimer = null;

        $(document).ready(function() {
            if ($('#tableJadwal').length) {
                $('#tableJadwal').DataTable({
                    pageLength: 25,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
                });
            }

            // Check if there's an active job on page load
            @if($activeJob && $activeJob->isActive())
                jobStartTime = new Date('{{ $activeJob->started_at ?? $activeJob->created_at }}');
                showStatus('{{ $activeJob->status }}');
                setButtonsDisabled(true);
                startPolling();
            @endif
        });

        function getAcademicYearId() {
            return document.getElementById('tahun_ajar_id').value;
        }

        function setButtonsDisabled(disabled) {
            const btnGen = document.getElementById('btnGenerate');
            const btnDel = document.getElementById('btnHapus');
            if (btnGen) {
                btnGen.disabled = disabled;
                btnGen.classList.toggle('opacity-50', disabled);
                btnGen.classList.toggle('cursor-not-allowed', disabled);
                btnGen.classList.toggle('hover:bg-amber-600', !disabled);
                btnGen.classList.toggle('hover:-translate-y-1', !disabled);
            }
            if (btnDel) {
                btnDel.disabled = disabled;
                btnDel.classList.toggle('opacity-50', disabled);
                btnDel.classList.toggle('cursor-not-allowed', disabled);
            }
        }

        function showStatus(status, errorMsg) {
            const bar = document.getElementById('jobStatusBar');
            const dot = document.getElementById('statusDot');
            const text = document.getElementById('statusText');
            const errDiv = document.getElementById('statusError');

            bar.classList.remove('hidden', 'bg-amber-50', 'border-amber-200', 'bg-green-50', 'border-green-200', 'bg-red-50', 'border-red-200', 'bg-blue-50', 'border-blue-200');
            dot.classList.remove('bg-amber-500', 'bg-green-500', 'bg-red-500', 'bg-blue-500');
            errDiv.classList.add('hidden');

            if (status === 'pending') {
                bar.classList.add('bg-blue-50', 'border-blue-200');
                dot.classList.add('bg-blue-500');
                text.textContent = 'Menunggu antrian... Job akan segera diproses oleh worker.';
            } else if (status === 'processing') {
                bar.classList.add('bg-amber-50', 'border-amber-200');
                dot.classList.add('bg-amber-500');
                text.textContent = 'OR-Tools sedang menyusun jadwal... Anda dapat menutup halaman ini, proses tetap berjalan.';
            } else if (status === 'completed') {
                bar.classList.add('bg-green-50', 'border-green-200');
                dot.classList.add('bg-green-500');
                dot.classList.remove('animate-pulse-dot');
                text.textContent = 'Jadwal berhasil digenerate! Memuat ulang halaman...';
            } else if (status === 'failed') {
                bar.classList.add('bg-red-50', 'border-red-200');
                dot.classList.add('bg-red-500');
                dot.classList.remove('animate-pulse-dot');
                text.textContent = 'Generate jadwal gagal.';
                if (errorMsg) {
                    errDiv.textContent = errorMsg;
                    errDiv.classList.remove('hidden');
                }
            }
        }

        function updateElapsed() {
            if (!jobStartTime) return;
            const el = document.getElementById('statusElapsed');
            const diff = Math.floor((Date.now() - jobStartTime.getTime()) / 1000);
            const m = Math.floor(diff / 60);
            const s = diff % 60;
            el.textContent = m > 0 ? `${m}m ${s}s` : `${s}s`;
        }

        function startPolling() {
            stopPolling();
            elapsedTimer = setInterval(updateElapsed, 1000);
            updateElapsed();
            pollTimer = setInterval(pollStatus, 3000);
        }

        function stopPolling() {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            if (elapsedTimer) { clearInterval(elapsedTimer); elapsedTimer = null; }
        }

        function pollStatus() {
            $.get(URL_STATUS, { tahun_ajar_id: getAcademicYearId() })
                .done(function(data) {
                    if (data.job_status === 'completed') {
                        stopPolling();
                        showStatus('completed');
                        setTimeout(function() {
                            window.location.href = URL_INDEX + '?tahun_ajar_id=' + getAcademicYearId();
                        }, 1500);
                    } else if (data.job_status === 'failed') {
                        stopPolling();
                        showStatus('failed', data.error_message);
                        setButtonsDisabled(false);
                    } else if (data.job_status === 'processing') {
                        if (data.started_at && !jobStartTime) {
                            jobStartTime = new Date(data.started_at);
                        }
                        showStatus('processing');
                    } else if (data.job_status === 'pending') {
                        showStatus('pending');
                    }
                })
                .fail(function() {
                    // Network error — keep polling
                });
        }

        const SCHEDULE_EXISTS = {{ (isset($jadwalList) && count($jadwalList) > 0) ? 'true' : 'false' }};

        function startGenerate() {
            if (SCHEDULE_EXISTS) {
                // Jadwal sudah pernah di-generate → tampilkan peringatan keras
                Swal.fire({
                    title: '<span style="color:#dc2626">⚠️ Jadwal Sudah Ada!</span>',
                    html: `<div style="text-align:left; font-size:14px; line-height:1.7;">
                        <p>Jadwal untuk tahun ajar ini <strong>sudah pernah digenerate</strong>.</p>
                        <p style="margin-top:8px;">Jika Anda melanjutkan, maka:</p>
                        <ul style="margin-top:4px; padding-left:20px; list-style:disc;">
                            <li>Seluruh <strong>jadwal lama akan dihapus</strong></li>
                            <li>Proses generate ulang memakan waktu <strong>beberapa menit</strong></li>
                            <li>Perubahan manual yang sudah dilakukan akan <strong>hilang</strong></li>
                        </ul>
                        <p style="margin-top:12px; color:#dc2626; font-weight:bold;">Apakah Anda yakin ingin menimpa jadwal yang sudah ada?</p>
                    </div>`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: '<i class="fa-solid fa-triangle-exclamation mr-1"></i> Ya, Generate Ulang!',
                    cancelButtonText: 'Batal, Pertahankan Jadwal',
                    reverseButtons: true,
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitGenerate();
                    }
                });
            } else {
                // Jadwal belum ada → konfirmasi biasa
                Swal.fire({
                    title: 'Mulai Auto-Generate?',
                    text: "Proses ini akan memakan waktu hingga beberapa menit. Lanjutkan?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: '<i class="fa-solid fa-rocket mr-1"></i> Ya, Generate!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitGenerate();
                    }
                });
            }
        }

        function submitGenerate() {
            setButtonsDisabled(true);

            $.ajax({
                url: URL_GENERATE,
                method: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                    tahun_ajar_id: getAcademicYearId()
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'ok') {
                        jobStartTime = new Date();
                        showStatus('pending');
                        startPolling();
                    } else {
                        Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                        setButtonsDisabled(false);
                    }
                },
                error: function(xhr) {
                    let msg = 'Terjadi kesalahan.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Gagal', msg, 'error');
                    setButtonsDisabled(false);
                }
            });
        }

        function switchAcademicYear(id) {
            stopPolling();
            window.location.href = URL_INDEX + '?tahun_ajar_id=' + id;
        }

        function deleteSchedule() {
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
