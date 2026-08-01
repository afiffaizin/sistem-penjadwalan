@extends('layouts.app')

@section('content')
    <div class="container mx-auto max-w-7xl">

        <div
            class="bg-gradient-to-r from-amber-700 to-amber-900 text-white p-8 rounded-2xl shadow-md mb-8 relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] rounded-2xl overflow-hidden pointer-events-none">
            </div>
            <div class="relative z-10">
                <h1 class="text-3xl font-black mb-1">Selamat Datang, Kepala Jurusan</h1>
                <p class="text-amber-200 text-sm font-medium">Panel Khusus Pemantauan dan Rekapitulasi Jadwal Kuliah Tingkat
                    Jurusan.</p>
            </div>
            <div class="relative z-50" id="custom-dropdown-container">
                <form action="{{ route('kajur.dashboard') }}" method="GET" id="tahun-ajar-form">
                    <input type="hidden" name="tahun_ajar_id" id="tahun_ajar_id_input" value="{{ $selectedTahunAjarId }}">
                    
                    <button type="button" onclick="toggleDropdown()" class="flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm px-5 py-2.5 rounded-xl border border-white/30 transition shadow-sm w-full md:w-auto">
                        <span class="text-sm font-bold text-amber-50">Tahun Ajaran:</span>
                        <span class="text-sm font-bold text-white ml-2" id="selected-text">
                            @php
                                $selectedTa = $tahunAjars->firstWhere('id', $selectedTahunAjarId);
                            @endphp
                            {{ $selectedTa ? $selectedTa->tahun . ' - ' . ucfirst($selectedTa->semester) : 'Pilih...' }}
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white ml-3 transition-transform duration-200" id="dropdown-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div id="custom-dropdown-menu" class="absolute right-0 md:left-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 hidden z-50 overflow-hidden transform origin-top transition-all">
                        <div class="py-2 flex flex-col">
                            @foreach($tahunAjars as $ta)
                                <button type="button" onclick="selectTahunAjar('{{ $ta->id }}')" class="w-full text-left px-5 py-3 text-sm font-bold transition flex justify-between items-center {{ $selectedTahunAjarId == $ta->id ? 'bg-orange-50 text-orange-700' : 'text-gray-800 hover:bg-gray-50' }}">
                                    <span>{{ $ta->tahun }} - {{ ucfirst($ta->semester) }}</span>
                                    @if($selectedTahunAjarId == $ta->id)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div
                    class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Dosen Mengajar Aktif</p>
                    <h3 class="text-2xl font-black text-gray-800 mt-0.5">{{ $totalDosen }} Orang</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div
                    class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-users-rectangle"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Kelas</p>
                    <h3 class="text-2xl font-black text-gray-800 mt-0.5">{{ $totalKelas }} Kelas</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div
                    class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-door-open"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Fasilitas Ruangan</p>
                    <h3 class="text-2xl font-black text-gray-800 mt-0.5">{{ $totalRuang }} Ruang</h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col">
                <h3 class="text-lg font-extrabold text-gray-800 mb-6"><i
                        class="fa-solid fa-chart-simple mr-1 text-amber-500"></i> Distribusi Beban SKS Prodi</h3>

                <div class="space-y-5 flex-grow justify-center flex flex-col">
                    @foreach ($bebanProdi as $prodi)
                        <div>
                            <div class="flex justify-between text-sm font-bold text-gray-700 mb-1.5">
                                <span>{{ $prodi['nama'] }}</span>
                                <span class="text-amber-600">{{ $prodi['total_sks'] }} SKS</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden shadow-inner">
                                <div class="bg-amber-500 h-3 rounded-full"
                                    style="width: {{ min(($prodi['total_sks'] / 200) * 100, 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col">
                <h3 class="text-lg font-extrabold text-gray-800 mb-2"><i
                        class="fa-solid fa-calendar-day mr-1 text-amber-500"></i> Kepadatan Jadwal Per Hari</h3>
                <p class="text-xs text-gray-500 mb-6 font-medium">Berdasarkan total sesi perkuliahan yang berjalan dalam
                    satu hari.</p>

                <div class="relative w-full flex-grow" style="min-height: 250px;">
                    <canvas id="kepadatanHariChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('kepadatanHariChart').getContext('2d');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    // Label hari
                    labels: {!! json_encode($kepadatanHari['label']) !!},
                    datasets: [{
                        label: 'Total Sesi Kuliah',
                        // Data (Angka kepadatan) diambil dari Controller
                        data: {!! json_encode($kepadatanHari['data']) !!},
                        backgroundColor: 'rgba(245, 158, 11, 0.2)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
                            titleFont: {
                                size: 13
                            },
                            bodyFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: 10,
                            displayColors: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6',
                                drawBorder: false,
                            },
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 11,
                                    weight: '500'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        });

        // Custom Dropdown JS
        function toggleDropdown() {
            const menu = document.getElementById('custom-dropdown-menu');
            const icon = document.getElementById('dropdown-icon');
            menu.classList.toggle('hidden');
            if (!menu.classList.contains('hidden')) {
                icon.classList.add('rotate-180');
            } else {
                icon.classList.remove('rotate-180');
            }
        }

        function selectTahunAjar(id) {
            document.getElementById('tahun_ajar_id_input').value = id;
            document.getElementById('tahun-ajar-form').submit();
        }

        document.addEventListener('click', function(event) {
            const container = document.getElementById('custom-dropdown-container');
            const menu = document.getElementById('custom-dropdown-menu');
            const icon = document.getElementById('dropdown-icon');
            
            if (container && !container.contains(event.target)) {
                if (!menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                    icon.classList.remove('rotate-180');
                }
            }
        });
    </script>
@endsection
