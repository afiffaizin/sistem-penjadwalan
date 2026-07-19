<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('images/jkblogo1.png') }}" type="image/png">
    <title>SI Penjadwalan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background-color: #fff;
            padding: 6px 12px;
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .select2-container--default .select2-selection--single:hover {
            border-color: #f59e0b;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.3);
            outline: none;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
            padding-left: 0;
            color: #1f2937;
            font-size: 0.875rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 8px;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6b7280;
        }
        .select2-dropdown {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.08);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 8px 12px;
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            outline: none;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
        }
        .select2-container--default .select2-results__option {
            padding: 8px 12px;
            font-size: 0.875rem;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #f59e0b;
            color: #fff;
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #fef3c7;
            color: #92400e;
        }
        .select2-container {
            width: 100% !important;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col">

    <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-40">
        <div class="container mx-auto max-w-7xl px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gray-100 text-white rounded-lg flex items-center justify-center font-bold text-xl shadow-sm">
                    <img src="{{ asset('images/jkblogo1.png') }}" alt="Logo" class="h-8 w-8 ">
                </div>
                <div>
                    <h1 class="font-extrabold text-lg leading-tight text-gray-900">Sistem Penjadwalan Perkuliahan</h1>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Jurusan Komputer dan Bisnis</p>
                </div>
            </div>
            
            <div>
                @auth
                    <a href="{{ route('dashboard') }}" class="px-5 py-2.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 font-bold text-sm rounded-lg transition shadow-sm flex items-center gap-2">
                        Masuk Dashboard <i class="fa-solid fa-arrow-right"></i>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm rounded-lg transition shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-right-to-bracket"></i> Login Sistem
                    </a>
                @endauth
            </div>
        </div>
    </nav>
    <main class="flex-grow py-8">
        @php
            $colorMap = [
                'bg-pink-200'   => '#fbcfe8',
                'bg-blue-200'   => '#bfdbfe',
                'bg-yellow-200' => '#fef08a',
                'bg-green-200'  => '#bbf7d0',
                'bg-purple-200' => '#e9d5ff',
                'bg-teal-200'   => '#99f6e4',
                'bg-red-200'    => '#fecaca',
                'bg-blue-50'    => '#eff6ff'
            ];
        @endphp

        <div class="container mx-auto max-w-7xl">
            
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl mb-6 text-sm font-bold shadow-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-green-500 text-lg"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6 text-sm font-bold shadow-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-500 text-lg"></i>
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border-l-8 border-amber-500">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight mb-2">Pencarian Jadwal Perkuliahan</h1>
                        <p class="text-gray-500 text-sm">Gunakan satu atau kombinasi filter di bawah untuk memunculkan jadwal perkuliahan.</p>
                    </div>
                </div>

                <form action="{{ route('welcome') }}" method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Program Studi</label>
                            <select name="prodi_id" class="select2-filter w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white cursor-pointer">
                                <option value="">-- Semua Prodi --</option>
                                @foreach($prodis as $p)
                                    <option value="{{ $p->id }}" {{ request('prodi_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Dosen Pengajar</label>
                            <select name="dosen_id" class="select2-filter w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white cursor-pointer">
                                <option value="">-- Semua Dosen --</option>
                                @foreach($dosens as $d)
                                    <option value="{{ $d->id }}" {{ request('dosen_id') == $d->id ? 'selected' : '' }}>{{ $d->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kelas</label>
                            <select name="kelas_id" class="select2-filter w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white cursor-pointer">
                                <option value="">-- Semua Kelas --</option>
                                @foreach($kelas as $k)
                                    <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ruangan</label>
                            <select name="ruang_id" class="select2-filter w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white cursor-pointer">
                                <option value="">-- Semua Ruangan --</option>
                                @foreach($ruangs as $r)
                                    <option value="{{ $r->id }}" {{ request('ruang_id') == $r->id ? 'selected' : '' }}>{{ $r->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-center gap-3 mt-4 pt-4 border-t border-gray-100">
                        <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                            @if(request()->anyFilled(['dosen_id', 'kelas_id', 'ruang_id', 'prodi_id']))
                                <button type="submit" 
                                        formaction="{{ route('jadwal.export.excel') }}" 
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold transition duration-150 shadow-sm shadow-emerald-100">
                                    <i class="fa-solid fa-file-excel text-base"></i>
                                    <span>Export Excel</span>
                                </button>

                                <button type="submit" 
                                        formaction="{{ route('jadwal.export.pdf') }}" 
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-bold transition duration-150 shadow-sm shadow-rose-100">
                                    <i class="fa-solid fa-file-pdf text-base"></i>
                                    <span>Export PDF</span>
                                </button>
                            @endif
                        </div>

                        <div class="flex w-full sm:w-auto justify-end gap-3">
                            <a href="{{ route('welcome') }}" class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-lg text-sm font-bold hover:bg-gray-200 transition text-center min-w-[80px]">Reset</a>
                            
                            <button type="submit" class="px-6 py-2.5 bg-amber-500 text-white rounded-lg text-sm font-bold hover:bg-amber-600 transition shadow-sm">
                                <i class="fa-solid fa-filter mr-2"></i> Tampilkan Jadwal
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            @if(request()->anyFilled(['dosen_id', 'kelas_id', 'ruang_id', 'prodi_id']))
                
                @php
                    $hanyaFilterProdi = request()->filled('prodi_id') && !request()->filled('dosen_id') && !request()->filled('kelas_id') && !request()->filled('ruang_id');
                @endphp

                @if($hanyaFilterProdi)
                    @php
                        $kelasDitemukan = [];
                        for ($s = 1; $s <= $totalSesi; $s++) {
                            foreach ($hariKerja as $h) {
                                foreach ($matrixJadwal[$s][$h] as $j) {
                                    if (!in_array($j['kelas'], $kelasDitemukan)) {
                                        $kelasDitemukan[] = $j['kelas'];
                                    }
                                }
                            }
                        }
                        foreach ($matkulMandiri as $m) {
                            if (!in_array($m['kelas'], $kelasDitemukan)) {
                                $kelasDitemukan[] = $m['kelas'];
                            }
                        }
                        sort($kelasDitemukan);
                    @endphp

                    @if(count($kelasDitemukan) > 0)
                        <div class="flex flex-col gap-8">
                            @foreach($kelasDitemukan as $namaKelas)
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 text-center">
                                        <h5 class="font-black text-lg text-gray-800 tracking-wide uppercase">KELAS {{ $namaKelas }}</h5>
                                    </div>
                                    <div class="overflow-x-auto flex-grow">
                                        <table class="min-w-full text-center border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider">
                                                    <th class="border border-gray-200 py-2 w-10 font-extrabold">Sesi</th>
                                                    @foreach($hariKerja as $hari)
                                                        <th class="border border-gray-200 py-2 w-1/5 font-extrabold">{{ $hari }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="text-xs">
                                                @for($s = 1; $s <= $totalSesi; $s++)
                                                    <tr>
                                                        <td class="border border-gray-200 py-2 font-extrabold bg-gray-50 text-gray-700">{{ $s }}</td>
                                                        @foreach($hariKerja as $hari)
                                                            @php $jadwalSelIni = collect($matrixJadwal[$s][$hari])->where('kelas', $namaKelas); @endphp
                                                            <td class="border border-gray-200 p-1.5 align-top bg-white">
                                                                <div class="flex flex-col gap-2 h-full">
                                                                    @forelse($jadwalSelIni as $jadwal)
                                                                        
                                                                        @php
                                                                            $twColor = $jadwal['warna'] ?? 'bg-blue-50';
                                                                            $hexColor = $colorMap[$twColor] ?? '#eff6ff';
                                                                        @endphp
                                                                        
                                                                        <div style="background-color: {{ $hexColor }};" class="border border-black/10 rounded p-1.5 text-left transition hover:shadow-md shadow-sm">
                                                                            <div class="font-extrabold text-gray-900 text-[11px] leading-tight mb-1">{{ $jadwal['mata_kuliah'] }}</div>
                                                                            <div class="flex flex-col gap-1 mt-1 border-t border-black/10 pt-1">
                                                                                <span class="text-[10px] font-bold text-gray-800 truncate" title="{{ $jadwal['dosen'] }}">
                                                                                    <i class="fa-solid fa-user-tie opacity-50 mr-0.5"></i> {{ explode(',', $jadwal['dosen'])[0] }}
                                                                                </span>
                                                                                <div class="flex justify-between items-center text-[9px] font-semibold text-gray-700">
                                                                                    <span class="bg-white/80 px-1 rounded border border-gray-200 shadow-sm"><i class="fa-solid fa-location-dot opacity-50 mr-0.5"></i> {{ $jadwal['ruang'] }}</span>
                                                                                    <span class="px-1 py-0.5 rounded text-white font-bold shadow-sm {{ strtolower($jadwal['jenis']) == 'praktikum' ? 'bg-indigo-600' : 'bg-amber-600' }}">{{ $jadwal['jenis'] }}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @empty
                                                                        <div class="h-full w-full flex items-center justify-center text-gray-200 font-light">-</div>
                                                                    @endforelse
                                                                </div>
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endfor
                                            </tbody>
                                        </table>
                                    </div>
                                    @php $mandiriKelasIni = $matkulMandiri->where('kelas', $namaKelas); @endphp
                                    @if($mandiriKelasIni->count() > 0)
                                        <div class="px-5 py-3 bg-green-50/50 border-t border-green-100">
                                            <p class="font-bold text-green-900 text-xs mb-2 flex items-center gap-1.5">
                                                <i class="fa-solid fa-graduation-cap text-green-600"></i> Info Kegiatan Mandiri / MBKM:
                                            </p>
                                            <ul class="flex flex-wrap gap-x-6 gap-y-2 text-xs text-gray-700">
                                                @foreach($mandiriKelasIni as $mm)
                                                    @php
                                                        $tampilDosen = !empty($mm['nama_dosen']) && strtolower($mm['nama_dosen']) !== 'tim' && strtolower($mm['nama_dosen']) !== 'mandiri' && !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                                                    @endphp
                                                    <li class="flex items-center">
                                                        <span class="mr-1.5 text-green-500 font-bold">•</span>
                                                        <span class="font-bold text-gray-800">{{ $mm['nama_matkul'] }}</span>
                                                        @if($tampilDosen) <span class="text-green-700 font-semibold ml-1">({{ $mm['nama_dosen'] }})</span> @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center mt-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-50 mb-3 border-2 border-red-100">
                                <i class="fa-solid fa-circle-exclamation text-2xl text-red-400"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-700">Data Tidak Ditemukan</h3>
                            <p class="text-gray-500 text-sm">Tidak ada kelas atau jadwal yang terdaftar untuk Program Studi ini.</p>
                        </div>
                    @endif

                @else
                    @php
                        $adaJadwalFisik = false;
                        for($s = 1; $s <= $totalSesi; $s++) {
                            foreach($hariKerja as $h) {
                                if(count($matrixJadwal[$s][$h]) > 0) {
                                    $adaJadwalFisik = true;
                                    break 2;
                                }
                            }
                        }
                    @endphp

                    @if($adaJadwalFisik)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="overflow-x-auto p-6">
                                <table class="w-full text-sm border-collapse border border-gray-200 text-center">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                                            <th class="border border-gray-200 py-3 w-16 font-extrabold">Sesi</th>
                                            @foreach($hariKerja as $hari)
                                                <th class="border border-gray-200 py-3 w-1/5 font-extrabold">{{ $hari }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @for($s = 1; $s <= $totalSesi; $s++)
                                            <tr>
                                                <td class="border border-gray-200 py-3 font-extrabold bg-gray-50 text-gray-700">{{ $s }}</td>
                                                @foreach($hariKerja as $hari)
                                                    <td class="border border-gray-200 p-2 align-top bg-white">
                                                        <div class="flex flex-col gap-2 h-full">
                                                            @forelse($matrixJadwal[$s][$hari] as $jadwal)
                                                                
                                                                @php
                                                                    $twColor = $jadwal['warna'] ?? 'bg-blue-50';
                                                                    $hexColor = $colorMap[$twColor] ?? '#eff6ff';
                                                                @endphp
                                                                
                                                                <div style="background-color: {{ $hexColor }};" class="border border-black/10 rounded p-2 text-left transition hover:shadow-md shadow-sm">
                                                                    <div class="font-extrabold text-gray-900 text-xs mb-1">{{ $jadwal['mata_kuliah'] }}</div>
                                                                    <div class="flex items-center justify-between mt-1 border-t border-black/10 pt-1">
                                                                        <span class="text-[10px] font-bold text-gray-800">
                                                                            <i class="fa-solid fa-user-tie opacity-50"></i> {{ explode(',', $jadwal['dosen'])[0] }}
                                                                        </span>
                                                                        <span class="text-[10px] bg-white/70 px-1 rounded font-extrabold text-gray-800 shadow-sm border border-white">{{ $jadwal['kelas'] }}</span>
                                                                    </div>
                                                                    <div class="mt-1 flex justify-between items-center text-[10px] font-semibold text-gray-700">
                                                                        <span><i class="fa-solid fa-location-dot opacity-50 mr-1"></i> {{ $jadwal['ruang'] }}</span>
                                                                        <span class="px-1.5 py-0.5 rounded text-white font-bold shadow-sm {{ strtolower($jadwal['jenis']) == 'praktikum' ? 'bg-indigo-600' : 'bg-amber-600' }}">{{ $jadwal['jenis'] }}</span>
                                                                    </div>
                                                                </div>
                                                            @empty
                                                                <div class="h-full w-full flex items-center justify-center text-gray-300 font-light">-</div>
                                                            @endforelse
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center mt-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-50 mb-4 border-2 border-red-100">
                                <i class="fa-solid fa-circle-exclamation text-2xl text-red-400"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-700 mb-1">Data Tidak Ditemukan</h3>
                            <p class="text-gray-500 text-sm max-w-sm mx-auto">Kombinasi pencarian Anda tidak cocok. Silakan coba kombinasikan filter dropdown yang lain.</p>
                        </div>
                    @endif
                @endif
                
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-16 text-center mt-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-amber-50 mb-5 border-4 border-amber-100">
                        <i class="fa-solid fa-calendar-days text-3xl text-amber-500"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Pilih Filter Jadwal</h3>
                    <p class="text-gray-500 text-sm max-w-md mx-auto">Silakan pilih spesifikasi pencarian melalui dropdown di atas, lalu klik "Tampilkan Jadwal".</p>
                </div>
            @endif
        </div>
    </main>
    <footer class="bg-white border-t border-gray-200 mt-auto py-6">
        <div class="container mx-auto max-w-7xl px-4 text-center text-gray-500 text-xs font-semibold">
            &copy; {{ date('Y') }} Sistem Informasi Penjadwalan Perkuliahan JKB. All rights reserved.
        </div>
    </footer>

    <!-- jQuery + Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-filter').each(function() {
                $(this).select2({
                    placeholder: $(this).find('option[value=""]').text() || 'Pilih...',
                    allowClear: true,
                    width: '100%'
                });
            });
        });
    </script>
    </body>
</html>