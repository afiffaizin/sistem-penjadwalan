@extends('layouts.app')

@section('content')
    <div class="container mx-auto max-w-7xl">

        <div
            class="bg-gradient-to-r from-amber-700 to-amber-900 text-white p-8 rounded-2xl shadow-md mb-8 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
            </div>
            <div class="relative z-10">
                <h1 class="text-3xl font-black mb-1">Selamat Datang, Kaprodi {{ $prodi->nama ?? 'Program Studi' }}</h1>
                <p class="text-amber-200 text-sm font-medium">Panel Pemantauan Jadwal Kuliah Internal Program Studi.</p>
            </div>
        </div>

        <!-- Statistik Card -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Dosen Mengajar</p>
                    <h3 class="text-2xl font-black text-gray-800 mt-0.5">{{ $totalDosen }} Orang</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div
                    class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Beban Mengajar</p>
                    <h3 class="text-2xl font-black text-gray-800 mt-0.5">{{ $totalSks }} SKS</h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col lg:col-span-2">
                <h3 class="text-lg font-extrabold text-gray-800 mb-2"><i
                        class="fa-solid fa-chart-column mr-1 text-amber-500"></i> Kepadatan Jadwal Per Hari</h3>
                <p class="text-xs text-gray-500 mb-6 font-medium">Distribusi total sesi perkuliahan aktif berdasarkan hari
                    kerja.</p>

                <div class="relative w-full flex-grow" style="min-height: 250px;">
                    <canvas id="kepadatanHariChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col">
                <h3 class="text-lg font-extrabold text-gray-800 mb-2"><i
                        class="fa-solid fa-chart-pie mr-1 text-amber-500"></i> Kategori Perkuliahan</h3>
                <p class="text-xs text-gray-500 mb-6 font-medium">Berdasarkan alokasi jenis ruangan yang digunakan.</p>

                <div class="relative w-full flex-grow flex items-center justify-center" style="min-height: 250px;">
                    <canvas id="jenisKuliahChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // GRAFIK Kepadatan Per Hari
            const ctxBar = document.getElementById('kepadatanHariChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($kepadatanHari['label']) !!},
                    datasets: [{
                        label: 'Total Sesi',
                        data: {!! json_encode($kepadatanHari['data']) !!},
                        backgroundColor: 'rgba(245, 158, 11, 0.2)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 2,
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // GRAFIK Teori vs Praktikum (Doughnut Chart)
            const ctxPie = document.getElementById('jenisKuliahChart').getContext('2d');
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($jenisKuliah['label']) !!},
                    datasets: [{
                        data: {!! json_encode($jenisKuliah['data']) !!},
                        backgroundColor: [
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(79, 70, 229, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
