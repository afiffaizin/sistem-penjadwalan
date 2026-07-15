@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="mb-8 mt-2">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Dashboard</h1>
        <p class="text-blue-600 font-medium mt-1 text-sm">Statistik Sistem Penjadwalan Aktif</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex justify-between items-center relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500 rounded-l-xl"></div>
            <div>
                <p class="text-xs font-bold text-gray-500 mb-1 tracking-wide">Jumlah Dosen</p>
                <h3 class="text-3xl font-black text-gray-800">{{ $jumlahDosen }}</h3>
            </div>
            <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500 text-xl shadow-sm border border-amber-100">
                <i class="fa-solid fa-user-group"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex justify-between items-center relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500 rounded-l-xl"></div>
            <div>
                <p class="text-xs font-bold text-gray-500 mb-1 tracking-wide">Jumlah Mata Kuliah</p>
                <h3 class="text-3xl font-black text-gray-800">{{ $jumlahMatkul }}</h3>
            </div>
            <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500 text-xl shadow-sm border border-amber-100">
                <i class="fa-solid fa-book-bookmark"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex justify-between items-center relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500 rounded-l-xl"></div>
            <div>
                <p class="text-xs font-bold text-gray-500 mb-1 tracking-wide">Jumlah Ruangan</p>
                <h3 class="text-3xl font-black text-gray-800">{{ $jumlahRuangan }}</h3>
            </div>
            <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500 text-xl shadow-sm border border-amber-100">
                <i class="fa-solid fa-building"></i>
            </div>
        </div>

    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-bold text-gray-800">Ringkasan Data Kurikulum</h2>
            @if($jumlahMatkul > 0)
                <a href="{{ route('matkul.index') }}" class="text-xs font-semibold text-gray-500 hover:text-amber-500 transition flex items-center gap-1">
                    Lihat detail <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            @endif
        </div>

        {{-- KONDISI 1: JIKA DATA MATA KULIAH / JADWAL SUDAH ADA, TAMPILKAN CHART --}}
        @if($jumlahMatkul > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 py-4">
                
                <div class="bg-gray-50/50 rounded-2xl p-6 border border-gray-100 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-1">Rasio Pembagian</h3>
                        <p class="text-gray-800 font-bold mb-6">Distribusi Tipe Mata Kuliah</p>
                    </div>
                    
                    <div id="donutChart" class="flex justify-center mb-6"></div>
                    
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-white p-2 rounded-lg border border-gray-200">
                            <span class="block w-2 h-2 bg-blue-500 rounded-full mx-auto mb-1"></span>
                            <p class="text-[10px] text-gray-500 font-bold">TEORI</p>
                            <p class="font-black text-blue-900">{{ $jumlahTeori ?? 0 }}</p>
                        </div>
                        <div class="bg-white p-2 rounded-lg border border-gray-200">
                            <span class="block w-2 h-2 bg-rose-500 rounded-full mx-auto mb-1"></span>
                            <p class="text-[10px] text-gray-500 font-bold">PRAKTIKUM</p>
                            <p class="font-black text-rose-900">{{ $jumlahPraktikum ?? 0 }}</p>
                        </div>
                        <div class="bg-white p-2 rounded-lg border border-gray-200">
                            <span class="block w-2 h-2 bg-amber-500 rounded-full mx-auto mb-1"></span>
                            <p class="text-[10px] text-gray-500 font-bold">CAMPURAN</p>
                            <p class="font-black text-amber-900">{{ $jumlahCampuran ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50/50 rounded-2xl p-6 border border-gray-100 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-1">Beban Mengajar</h3>
                        <p class="text-gray-800 font-bold mb-6">Top 5 Dosen SKS Tertinggi</p>
                    </div>
                    
                    <div id="barChart" class="w-full"></div>
                </div>

            </div>

        {{-- KONDISI 2: JIKA BELUM ADA DATA, TAMPILKAN STATE BELUM TERSEDIA --}}
        @else
            <div class="border-2 border-dashed border-gray-200 bg-gray-50/50 rounded-2xl p-10 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 bg-white rounded-full shadow-sm flex items-center justify-center mb-5 text-gray-400 text-2xl border border-gray-100">
                    <i class="fa-solid fa-chart-column"></i>
                </div>
                
                <h3 class="text-xl font-bold text-gray-800 mb-2">Visualisasi Data Belum Tersedia</h3>
                <p class="text-gray-500 max-w-lg mb-8 text-sm leading-relaxed">
                    Silahkan selesaikan proses upload data dan cleansing untuk melihat statistik mendalam mengenai distribusi jam kuliah dan ketersediaan ruangan.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('upload.form') }}" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-full transition shadow-md shadow-amber-200 border border-transparent">
                        Mulai Upload Data
                    </a>
                    <a href="#" class="px-6 py-2.5 bg-white border border-gray-300 hover:border-amber-500 hover:text-amber-600 text-gray-600 text-sm font-semibold rounded-full transition shadow-sm">
                        Pelajari Sistem
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>


{{-- Script ApexCharts Hanya Dimuat Jika Data Tersedia --}}
@if($jumlahMatkul > 0)
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // --- 1. SETUP DONUT CHART ---
        var donutOptions = {
            series: [{{ $jumlahTeori ?? 0 }}, {{ $jumlahPraktikum ?? 0 }}, {{ $jumlahCampuran ?? 0 }}],
            chart: { type: 'donut', height: 280 },
            labels: ['Murni Teori', 'Murni Praktikum', 'Campuran'],
            colors: ['#3b82f6', '#f43f5e', '#f59e0b'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total MK',
                                color: '#94a3b8',
                                formatter: function (w) { return w.globals.seriesSum }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },git commit -m "fix: resolve HTTP 500 error on sekjur dashboard"
            legend: { show: false },
            stroke: { width: 0 }
        };
        var donutChart = new ApexCharts(document.querySelector("#donutChart"), donutOptions);
        donutChart.render();

        // --- 2. SETUP STACKED BAR CHART ---
        var barOptions = {
            series: [{
                name: 'SKS Teori',
                data: {!! json_encode($chartDosenTeori ?? []) !!}
            }, {
                name: 'SKS Praktikum',
                data: {!! json_encode($chartDosenPraktikum ?? []) !!}
            }],
            chart: {
                type: 'bar',
                height: 320,
                stacked: true, // Membuat datanya bertumpuk
                toolbar: { show: false }
            },
            colors: ['#3b82f6', '#f43f5e'], // Biru untuk Teori, Merah/Rose untuk Praktikum
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                },
            },
            xaxis: {
                categories: {!! json_encode($chartDosenLabels ?? []) !!},
                labels: { style: { colors: '#64748b' } }
            },
            yaxis: {
                labels: { style: { colors: '#334155', fontWeight: 600 } }
            },
            dataLabels: { enabled: true },
            legend: { 
                position: 'top', 
                horizontalAlign: 'left',
                markers: { radius: 12 }
            }
        };
        var barChart = new ApexCharts(document.querySelector("#barChart"), barOptions);
        barChart.render();
    </script>
@endif
@endsection