@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-7xl">
    
    <div class="bg-gradient-to-r from-amber-700 to-amber-900 text-white p-8 rounded-2xl shadow-md mb-8 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
        <div class="relative z-10">
            <h1 class="text-3xl font-black mb-1">Selamat Datang, Kepala Jurusan</h1>
            <p class="text-amber-200 text-sm font-medium">Panel Khusus Pemantauan dan Rekapitulasi Jadwal Kuliah Tingkat Jurusan.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Dosen Mengajar Aktif</p>
                <h3 class="text-2xl font-black text-gray-800 mt-0.5">{{ $totalDosen }} Orang</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-users-rectangle"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Rombongan Kelas</p>
                <h3 class="text-2xl font-black text-gray-800 mt-0.5">{{ $totalKelas }} Kelas</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-xl font-bold">
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
            <h3 class="text-lg font-extrabold text-gray-800 mb-6"><i class="fa-solid fa-chart-simple mr-1 text-amber-500"></i> Distribusi Beban SKS Prodi</h3>
            
            <div class="space-y-5 flex-grow justify-center flex flex-col">
                @foreach($bebanProdi as $prodi)
                    <div>
                        <div class="flex justify-between text-sm font-bold text-gray-700 mb-1.5">
                            <span>{{ $prodi['nama'] }}</span>
                            <span class="text-amber-600">{{ $prodi['total_sks'] }} SKS</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden shadow-inner">
                            <div class="bg-amber-500 h-3 rounded-full" style="width: {{ min(($prodi['total_sks'] / 200) * 100, 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col">
            <h3 class="text-lg font-extrabold text-gray-800 mb-2"><i class="fa-solid fa-calendar-day mr-1 text-amber-500"></i> Kepadatan Jadwal Per Hari</h3>
            <p class="text-xs text-gray-500 mb-6 font-medium">Berdasarkan total sesi perkuliahan yang berjalan dalam satu hari.</p>
            
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
                        titleFont: { size: 13 },
                        bodyFont: { size: 14, weight: 'bold' },
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
                            font: { size: 11, weight: '500' }
                        }
                    },
                    x: {
                        grid: {
                            display: false, 
                            drawBorder: false,
                        },
                        ticks: {
                            font: { size: 12, weight: 'bold' }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection