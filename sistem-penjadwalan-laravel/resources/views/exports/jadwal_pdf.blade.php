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

        th,
        td {
            border: 1px solid #4b5563;
            padding: 5px;
            text-align: center;
            vertical-align: top;
        }

        th {
            font-weight: bold;
            background-color: #ffffff;
            color: black;
            font-size: 10px;
            text-transform: uppercase;
            height: 20px;
            vertical-align: middle;
        }

        /* ==========================================
        HEADER FILTER METADATA STYLE (NEW)
        ========================================== */
        .report-header {
            text-align: left;
            margin-bottom: 12px;
            padding-bottom: 10px;
            /* border-bottom: 2px solid #1e3a8a; */
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .report-subtitle {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0 0 8px 0;
            text-transform: uppercase;
        }

        .filter-table {
            border: none;
            margin: 0;
            padding: 0;
            border-collapse: collapse;
            width: auto;
        }

        .filter-table td {
            border: none;
            padding: 2px 0;
            font-size: 10px;
            text-align: left;
            vertical-align: top;
        }

        .filter-label {
            color: #111827;
            width: 110px;
            font-weight: bold;
        }

        .filter-separator {
            color: #111827;
            font-weight: bold;
            width: 12px;
        }

        .filter-value {
            color: #111827;
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
            border: 1px solid rgba(0, 0, 0, 0.1);
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
    <div class="report-header">
        <p class="report-title">JADWAL PERKULIAHAN</p>
        <p class="report-subtitle">JURUSAN KOMPUTER DAN BISNIS</p>
        @if (isset($filterLabels))
            <table class="filter-table">
                @foreach ($filterLabels as $label => $value)
                    <tr>
                        <td class="filter-label">{{ $label }}</td>
                        <td class="filter-separator">:</td>
                        <td class="filter-value">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    @php
        $hanyaFilterProdi = $tampilPerKelas ?? false;

        $colorMap = [
            'bg-pink-200' => '#fbcfe8',
            'bg-blue-200' => '#bfdbfe',
            'bg-yellow-200' => '#fef08a',
            'bg-green-200' => '#bbf7d0',
            'bg-purple-200' => '#e9d5ff',
            'bg-teal-200' => '#99f6e4',
            'bg-red-200' => '#fecaca',
            'bg-blue-50' => '#eff6ff',
        ];
    @endphp

    @if ($hanyaFilterProdi)
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

        @foreach ($kelasDitemukan as $index => $namaKelas)
            <div class="{{ $index > 0 ? 'page-break' : '' }}">
                <table>
                    <thead>
                        <tr>
                            <th colspan="6" class="header-kelas">KELAS {{ strtoupper($namaKelas) }}</th>
                        </tr>
                        <tr>
                            <th class="col-sesi">Sesi</th>
                            @foreach ($hariKerja as $hari)
                                <th style="width: 18.6%;">{{ strtoupper($hari) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for ($s = 1; $s <= $totalSesi; $s++)
                            <tr>
                                <td class="col-sesi">{{ $s }}</td>
                                @foreach ($hariKerja as $hari)
                                    @php $jadwalSelIni = collect($matrixJadwal[$s][$hari])->where('kelas', $namaKelas); @endphp
                                    <td>
                                        @foreach ($jadwalSelIni as $jadwal)
                                            @php
                                                $twColor = $jadwal['warna'] ?? 'bg-blue-50';
                                                $hexColor = $colorMap[$twColor] ?? '#eff6ff';
                                            @endphp
                                            <div class="card-jadwal" style="background-color: {{ $hexColor }};">
                                                <span class="matkul-title">{{ $jadwal['mata_kuliah'] }}</span><br>
                                                <span class="jadwal-detail">Dosen:
                                                    {{ explode(',', $jadwal['dosen'])[0] }}</span><br>
                                                <span class="jadwal-detail">R: {{ $jadwal['ruang'] }}
                                                    ({{ $jadwal['jenis'] }})
                                                </span>
                                            </div>
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        @endfor

                        @php $mandiriKelasIni = $matkulMandiri->where('kelas', $namaKelas); @endphp
                        @if ($mandiriKelasIni->count() > 0)
                            <tr>
                                <td colspan="6" class="bg-mbkm-header">Info Kegiatan Mandiri / MBKM:</td>
                            </tr>
                            @foreach ($mandiriKelasIni as $mm)
                                @php
                                    $tampilDosen =
                                        !empty($mm['nama_dosen']) &&
                                        strtolower($mm['nama_dosen']) !== 'tim' &&
                                        strtolower($mm['nama_dosen']) !== 'mandiri' &&
                                        !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                                @endphp
                                <tr>
                                    <td colspan="6" class="bg-mbkm-item">
                                        • {{ $mm['nama_matkul'] }} @if ($tampilDosen)
                                            ({{ $mm['nama_dosen'] }})
                                        @endif
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
                    @foreach ($hariKerja as $hari)
                        <th style="width: 18.6%;">{{ strtoupper($hari) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for ($s = 1; $s <= $totalSesi; $s++)
                    <tr>
                        <td class="col-sesi">{{ $s }}</td>
                        @foreach ($hariKerja as $hari)
                            <td>
                                @foreach ($matrixJadwal[$s][$hari] as $jadwal)
                                    @php
                                        $twColor = $jadwal['warna'] ?? 'bg-blue-50';
                                        $hexColor = $colorMap[$twColor] ?? '#eff6ff';
                                    @endphp
                                    <div class="card-jadwal" style="background-color: {{ $hexColor }};">
                                        <span class="matkul-title">{{ $jadwal['mata_kuliah'] }}</span><br>
                                        <span class="jadwal-detail">Dosen:
                                            {{ explode(',', $jadwal['dosen'])[0] }}</span><br>
                                        <span class="jadwal-detail">Kls: {{ $jadwal['kelas'] }} | R:
                                            {{ $jadwal['ruang'] }}</span>
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endfor

                @if ($matkulMandiri->count() > 0)
                    <tr>
                        <td colspan="6" class="bg-mbkm-header">Info Kegiatan Mandiri / MBKM:</td>
                    </tr>
                    @foreach ($matkulMandiri as $mm)
                        @php
                            $tampilDosen =
                                !empty($mm['nama_dosen']) &&
                                strtolower($mm['nama_dosen']) !== 'tim' &&
                                strtolower($mm['nama_dosen']) !== 'mandiri' &&
                                !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                        @endphp
                        <tr>
                            <td colspan="6" class="bg-mbkm-item">
                                • {{ $mm['nama_matkul'] }} - Kelas {{ $mm['kelas'] }} @if ($tampilDosen)
                                    ({{ $mm['nama_dosen'] }})
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    @endif

</body>

</html>
