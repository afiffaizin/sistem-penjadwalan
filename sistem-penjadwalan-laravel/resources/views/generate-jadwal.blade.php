@extends('layouts.app')

@section('content')
    @php
        $jumlahJadwal = (is_object($jadwalList) && method_exists($jadwalList, 'total')) ? $jadwalList->total() : count($jadwalList);
        $hasTahunAjar = $hasTahunAjar ?? $daftarTahunAjar->isNotEmpty();
    @endphp

    <style>
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
                            <select name="tahun_ajar_id" id="tahun_ajar_id" onchange="switchAcademicYear(this.value)" {{ !$hasTahunAjar ? 'disabled' : '' }}
                                class="w-full min-w-[240px] bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-amber-500 focus:border-amber-500 block p-2.5 pr-8 font-semibold shadow-sm appearance-none {{ $hasTahunAjar ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                                @forelse($daftarTahunAjar as $ta)
                                    <option value="{{ $ta->id }}" {{ $ta->id == $tahunAjarAktif?->id ? 'selected' : '' }}>
                                        {{ $ta->tahun }} - {{ $ta->semester }}
                                        @if($ta->is_active) (Aktif Sistem) @endif
                                    </option>
                                @empty
                                    <option value="">Belum ada tahun ajar</option>
                                @endforelse
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto self-end md:self-center">
                    <button type="button" id="btnGenerate" onclick="startGenerate()" {{ !$hasTahunAjar ? 'disabled' : '' }} class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-xl shadow-md shadow-amber-200 transition transform hover:-translate-y-1 flex items-center justify-center gap-2 {{ !$hasTahunAjar ? 'opacity-50 cursor-not-allowed hover:bg-amber-500 hover:translate-y-0' : '' }}">
                        <i class="fa-solid fa-wand-magic-sparkles text-xl"></i> Mulai Auto-Generate
                    </button>
                    @if($jumlahJadwal > 0)
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

        @if(!$hasTahunAjar)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-5 py-4 rounded-xl flex items-start mb-6 shadow-sm">
                <div class="bg-amber-100 text-amber-600 w-10 h-10 rounded-lg flex items-center justify-center mr-3 shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-amber-900">Data belum tersedia</h3>
                    <p class="text-sm mt-1 leading-relaxed">
                        Belum ada data tahun ajar dari proses upload. Silakan upload data terlebih dahulu sebelum menjalankan auto-generate jadwal.
                    </p>
                    <a href="{{ route('upload.form') }}" class="inline-flex items-center gap-2 mt-3 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm shadow-amber-200 transition">
                        <i class="fa-solid fa-file-arrow-up"></i> Upload Data
                    </a>
                </div>
            </div>
        @endif

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

        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-4">
                <div class="flex items-center">
                    <div class="bg-amber-100 text-amber-600 w-10 h-10 rounded-lg flex items-center justify-center mr-3">
                        <i class="fa-regular fa-calendar-days text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Daftar Jadwal Mata Kuliah</h2>
                        <p class="text-gray-500 text-sm mt-0.5">Hasil generate jadwal untuk tahun ajar yang dipilih.</p>
                    </div>
                </div>
            </div>

            @if($jumlahJadwal > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                                <th class="px-6 py-4 w-24 font-bold">Hari</th>
                                <th class="px-6 py-4 w-32 font-bold">Sesi</th>
                                <th class="px-6 py-4 w-32 font-bold">Kelas</th>
                                <th class="px-6 py-4 font-bold">Mata Kuliah</th>
                                <th class="px-6 py-4 font-bold">Dosen</th>
                                <th class="px-6 py-4 font-bold">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($jadwalList as $j)
                                <tr class="hover:bg-amber-50/50 transition">
                                    <td class="px-6 py-4 text-sm font-bold text-amber-600">{{ $j->hari }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 font-semibold">
                                        <span class="bg-gray-50 text-gray-700 px-2.5 py-1 rounded-md border border-gray-200 text-xs font-bold">
                                            Sesi {{ $j->sesi_mulai }} - {{ $j->sesi_selesai }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-md border border-indigo-100 text-xs font-semibold">
                                            {{ $j->kelas->nama ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-gray-800">{{ $j->mata_kuliah->nama ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $j->dosen->nama ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 font-medium">{{ $j->ruang->nama ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $jadwalList->withQueryString()->links() }}
                </div>
            @else
                <div class="py-14 text-center px-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 border border-gray-100 mb-4">
                        <i class="fa-solid fa-folder-open text-3xl text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 mb-1">
                        {{ $hasTahunAjar ? 'Jadwal Belum Digenerate' : 'Belum Ada Data Jadwal' }}
                    </h3>
                    <p class="text-gray-500 text-sm max-w-md mx-auto">
                        {{ $hasTahunAjar ? 'Silakan klik tombol Mulai Auto-Generate di atas untuk memproses jadwal.' : 'Data tahun ajar belum tersedia. Upload data terlebih dahulu agar sistem dapat menyiapkan proses generate jadwal.' }}
                    </p>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const CSRF_TOKEN = '{{ csrf_token() }}';
        const URL_GENERATE = '{{ route("jadwal.generate") }}';
        const URL_STATUS = '{{ route("jadwal.generate.status") }}';
        const URL_INDEX = '{{ route("jadwal.index") }}';
        const HAS_TAHUN_AJAR = {{ $hasTahunAjar ? 'true' : 'false' }};

        let pollTimer = null;
        let jobStartTime = null;
        let elapsedTimer = null;

        $(document).ready(function() {
            if (!HAS_TAHUN_AJAR) return;
            if (restoreGeneratingAcademicYear()) return;

            // Check if there's an active job on page load
            @if($activeJob && $activeJob->isActive())
                jobStartTime = new Date('{{ $activeJob->started_at ?? $activeJob->created_at }}');
                showStatus('{{ $activeJob->status }}');
                setButtonsDisabled(true);
                startPolling();
            @endif

            checkStatusOnLoad();
        });

        function getAcademicYearId() {
            const select = document.getElementById('tahun_ajar_id');
            return select ? select.value : '';
        }

        function getGeneratingAcademicYearId() {
            return sessionStorage.getItem('generating_tahun_ajar_id') || getAcademicYearId();
        }

        function restoreGeneratingAcademicYear() {
            const generatingYearId = sessionStorage.getItem('generating_tahun_ajar_id');
            const select = document.getElementById('tahun_ajar_id');

            if (generatingYearId && select && select.value !== generatingYearId) {
                window.location.href = URL_INDEX + '?tahun_ajar_id=' + generatingYearId;
                return true;
            }

            return false;
        }

        function isActiveStatus(status) {
            return status === 'pending' || status === 'processing';
        }

        function handleStatusResponse(data, options = {}) {
            if (data.job_status === 'completed') {
                sessionStorage.removeItem('generating_tahun_ajar_id');
                stopPolling();
                showStatus('completed');
                setButtonsDisabled(false);

                if (!options.skipReload) {
                    setTimeout(function() {
                        window.location.href = URL_INDEX + '?tahun_ajar_id=' + getGeneratingAcademicYearId();
                    }, 1500);
                }
            } else if (data.job_status === 'failed') {
                sessionStorage.removeItem('generating_tahun_ajar_id');
                stopPolling();
                showStatus('failed', data.error_message);
                setButtonsDisabled(false);
            } else if (data.job_status === 'processing') {
                if (data.started_at) {
                    jobStartTime = new Date(data.started_at);
                } else if (!jobStartTime) {
                    jobStartTime = new Date(data.created_at || Date.now());
                }
                sessionStorage.setItem('generating_tahun_ajar_id', getAcademicYearId());
                setButtonsDisabled(true);
                showStatus('processing');
                if (!pollTimer) startPolling();
            } else if (data.job_status === 'pending') {
                if (!jobStartTime) {
                    jobStartTime = new Date(data.created_at || Date.now());
                }
                sessionStorage.setItem('generating_tahun_ajar_id', getAcademicYearId());
                setButtonsDisabled(true);
                showStatus('pending');
                if (!pollTimer) startPolling();
            }
        }

        function checkStatusOnLoad() {
            $.get(URL_STATUS, { tahun_ajar_id: getAcademicYearId() })
                .done(function(data) {
                    if (isActiveStatus(data.job_status)) {
                        handleStatusResponse(data);
                    } else {
                        sessionStorage.removeItem('generating_tahun_ajar_id');
                    }
                })
                .fail(function() {
                    // Ignore initial network error; manual generate can still start polling.
                });
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
                dot.classList.add('bg-blue-500', 'animate-pulse-dot');
                text.textContent = 'Menunggu antrian... Job akan segera diproses oleh worker.';
            } else if (status === 'processing') {
                bar.classList.add('bg-amber-50', 'border-amber-200');
                dot.classList.add('bg-amber-500', 'animate-pulse-dot');
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
                    handleStatusResponse(data);
                })
                .fail(function() {
                    // Network error — keep polling
                });
        }

        const SCHEDULE_EXISTS = {{ $jumlahJadwal > 0 ? 'true' : 'false' }};

        function startGenerate() {
            const btnGen = document.getElementById('btnGenerate');
            if (!HAS_TAHUN_AJAR || !getAcademicYearId() || (btnGen && btnGen.disabled)) return;

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
            if (!HAS_TAHUN_AJAR || !getAcademicYearId()) {
                Swal.fire('Data belum tersedia', 'Upload data terlebih dahulu sebelum menjalankan auto-generate.', 'warning');
                return;
            }

            sessionStorage.setItem('generating_tahun_ajar_id', getAcademicYearId());
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
                    if (data.status === 'success') {
                        jobStartTime = new Date();
                        showStatus('pending');
                        startPolling();
                    } else {
                        sessionStorage.removeItem('generating_tahun_ajar_id');
                        Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                        setButtonsDisabled(false);
                    }
                },
                error: function(xhr) {
                    sessionStorage.removeItem('generating_tahun_ajar_id');
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
            if (!id) return;
            stopPolling();
            window.location.href = URL_INDEX + '?tahun_ajar_id=' + id;
        }

        function deleteSchedule() {
            const select = document.getElementById('tahun_ajar_id');
            if (!select || !select.value) return;

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
@endpush
