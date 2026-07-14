<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Jadwal Kuliah</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #222222;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-before: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #4b5563;
            padding: 5px;
            text-align: center;
            vertical-align: top;
        }
        th {
            font-weight: bold;
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            height: 20px;
            vertical-align: middle;
        }

        /* ==========================================
        HEADER FILTER METADATA STYLE (NEW)
        ========================================== */
        .filter-header-box {
            background-color: #f8fafc;
            border-left: 5px solid #1e3a8a;
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 14px;
            margin-bottom: 15px;
            border-radius: 0 6px 6px 0;
        }
        .filter-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-meta-text {
            font-size: 10px;
            color: #475569;
            margin: 0;
            line-height: 1.4;
        }
        .badge-filter {
            background-color: #e2e8f0;
            color: #0f172a;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: bold;
        }
        .header-kelas {
            background-color: #f59e0b;
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            height: 25px;
            vertical-align: middle;
        }
        .col-sesi {
            width: 7%;
            background-color: #f3f4f6;
            font-weight: bold;
            vertical-align: middle;
            font-size: 11px;
        }
        .card-jadwal {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 3px;
            padding: 5px;
            text-align: left;
            margin-bottom: 3px;
        }
        .matkul-title {
            font-weight: bold;
            color: #000000;
            font-size: 10px;
        }
        .jadwal-detail {
            color: #374151;
            font-size: 8.5px;
            line-height: 1.2;
        }
        .bg-mbkm-header {
            background-color: #f0fdf4;
            color: #14532d;
            font-weight: bold;
            text-align: left;
            padding: 5px;
        }
        .bg-mbkm-item {
            background-color: #f0fdf4;
            color: #14532d;
            text-align: left;
            padding: 4px 8px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    @php
    $filterInfo = [];
    
    if(request()->filled('prodi_id')) {
        $prodi = \App\Models\ProgramStudi::find(request('prodi_id'));
        $filterInfo[] = "Program Studi: <span class='badge-filter'>" . ($prodi->nama ?? 'Umum') . "</span>";
    }
    if(request()->filled('dosen_id')) {
        $dosen = \App\Models\Dosen::find(request('dosen_id'));
        $filterInfo[] = "Dosen: <span class='badge-filter'>" . ($dosen->nama ?? '-') . "</span>";
    }
    if(request()->filled('kelas_id')) {
        $kelasObj = \App\Models\Kelas::find(request('kelas_id'));
        $filterInfo[] = "Kelas: <span class='badge-filter'>" . ($kelasObj->nama ?? '-') . "</span>";
    }
    if(request()->filled('ruang_id')) {
        $ruang = \App\Models\Ruang::find(request('ruang_id'));
        $filterInfo[] = "Ruangan: <span class='badge-filter'>" . ($ruang->nama ?? '-') . "</span>";
    }
    if(request()->filled('tahun_ajar_id')) {
        $ta = \App\Models\TahunAjar::find(request('tahun_ajar_id'));
        if($ta) {
            $filterInfo[] = "Tahun Akademik: <span class='badge-filter'>" . $ta->tahun . " (" . ucfirst($ta->semester) . ")</span>";
        }
    }

    $stringFilter = count($filterInfo) > 0 ? implode(' | ', $filterInfo) : 'Semua Jadwal Perkuliahan';
@endphp

<div class="filter-header-box">
    <h1 class="filter-title">JADWAL PERKULIAHAN JURUSAN KOMPUTER DAN BISNIS</h1>
    <p class="filter-meta-text">
        <strong>Kriteria Pencarian:</strong> {!! $stringFilter !!}
    </p>
</div>

@php
    $hanyaFilterProdi = request()->filled('prodi_id') && !request()->filled('dosen_id') && !request()->filled('kelas_id') && !request()->filled('ruang_id');

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

    @foreach($kelasDitemukan as $index => $namaKelas)
        <div class="{{ $index > 0 ? 'page-break' : '' }}">
            <table>
                <thead>
                    <tr>
                        <th colspan="6" class="header-kelas">KELAS {{ strtoupper($namaKelas) }}</th>
                    </tr>
                    <tr>
                        <th class="col-sesi">Sesi</th>
                        @foreach($hariKerja as $hari)
                            <th style="width: 18.6%;">{{ strtoupper($hari) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for($s = 1; $s <= $totalSesi; $s++)
                        <tr>
                            <td class="col-sesi">{{ $s }}</td>
                            @foreach($hariKerja as $hari)
                                @php $jadwalSelIni = collect($matrixJadwal[$s][$hari])->where('kelas', $namaKelas); @endphp
                                <td>
                                    @foreach($jadwalSelIni as $jadwal)
                                        @php
                                            $twColor = $jadwal['warna'] ?? 'bg-blue-50';
                                            $hexColor = $colorMap[$twColor] ?? '#eff6ff';
                                        @endphp
                                        <div class="card-jadwal" style="background-color: {{ $hexColor }};">
                                            <span class="matkul-title">{{ $jadwal['mata_kuliah'] }}</span><br>
                                            <span class="jadwal-detail">Dosen: {{ explode(',', $jadwal['dosen'])[0] }}</span><br>
                                            <span class="jadwal-detail">R: {{ $jadwal['ruang'] }} ({{ $jadwal['jenis'] }})</span>
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endfor

                    @php $mandiriKelasIni = $matkulMandiri->where('kelas', $namaKelas); @endphp
                    @if($mandiriKelasIni->count() > 0)
                        <tr>
                            <td colspan="6" class="bg-mbkm-header">Info Kegiatan Mandiri / MBKM:</td>
                        </tr>
                        @foreach($mandiriKelasIni as $mm)
                            @php
                                $tampilDosen = !empty($mm['nama_dosen']) && strtolower($mm['nama_dosen']) !== 'tim' && strtolower($mm['nama_dosen']) !== 'mandiri' && !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                            @endphp
                            <tr>
                                <td colspan="6" class="bg-mbkm-item">
                                    • {{ $mm['nama_matkul'] }} @if($tampilDosen) ({{ $mm['nama_dosen'] }}) @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach

@else
    {{-- matriks tabel jadwal aja  --}}
    <table>
        <thead>
            <tr>
                <th class="col-sesi">Sesi</th>
                @foreach($hariKerja as $hari)
                    <th style="width: 18.6%;">{{ strtoupper($hari) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for($s = 1; $s <= $totalSesi; $s++)
                <tr>
                    <td class="col-sesi">{{ $s }}</td>
                    @foreach($hariKerja as $hari)
                        <td>
                            @foreach($matrixJadwal[$s][$hari] as $jadwal)
                                @php
                                    $twColor = $jadwal['warna'] ?? 'bg-blue-50';
                                    $hexColor = $colorMap[$twColor] ?? '#eff6ff';
                                @endphp
                                <div class="card-jadwal" style="background-color: {{ $hexColor }};">
                                    <span class="matkul-title">{{ $jadwal['mata_kuliah'] }}</span><br>
                                    <span class="jadwal-detail">Dosen: {{ explode(',', $jadwal['dosen'])[0] }}</span><br>
                                    <span class="jadwal-detail">Kls: {{ $jadwal['kelas'] }} | R: {{ $jadwal['ruang'] }}</span>
                                </div>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endfor

            @if($matkulMandiri->count() > 0)
                <tr>
                    <td colspan="6" class="bg-mbkm-header">Info Kegiatan Mandiri / MBKM:</td>
                </tr>
                @foreach($matkulMandiri as $mm)
                    @php
                        $tampilDosen = !empty($mm['nama_dosen']) && strtolower($mm['nama_dosen']) !== 'tim' && strtolower($mm['nama_dosen']) !== 'mandiri' && !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                    @endphp
                    <tr>
                        <td colspan="6" class="bg-mbkm-item">
                            • {{ $mm['nama_matkul'] }} - Kelas {{ $mm['kelas'] }} @if($tampilDosen) ({{ $mm['nama_dosen'] }}) @endif
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
@endif

</body>
</html>