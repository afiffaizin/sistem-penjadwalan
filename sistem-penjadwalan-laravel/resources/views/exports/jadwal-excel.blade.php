@php
    $hanyaFilterProdi = $tampilPerKelas ?? false;

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

    @foreach($kelasDitemukan as $namaKelas)
        @include('exports.partials.jadwal-filter-header')

        <table style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th colspan="6" style="text-align: center; font-weight: bold; font-size: 13px; background-color: #f59e0b; color: #ffffff; height: 25px; border: 1px solid #4b5563; vertical-align: middle;">
                        KELAS {{ strtoupper($namaKelas) }}
                    </th>
                </tr>
                <tr>
                    <th style="font-weight: bold; text-align: center; background-color: #ffffff; color: #000000; font-size: 10px; text-transform: uppercase; border: 1px solid #4b5563; height: 20px; vertical-align: middle;">Sesi</th>
                    @foreach($hariKerja as $hari)
                        <th style="font-weight: bold; text-align: center; background-color: #ffffff; color: #000000; font-size: 10px; text-transform: uppercase; border: 1px solid #4b5563; height: 20px; vertical-align: middle;">{{ strtoupper($hari) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($s = 1; $s <= $totalSesi; $s++)
                    <tr>
                        <td style="font-weight: bold; text-align: center; background-color: #f3f4f6; color: #1f2937; border: 1px solid #4b5563; padding: 5px; font-size: 11px; vertical-align: middle;">{{ $s }}</td>
                        
                        @foreach($hariKerja as $hari)
                            @php 
                                $jadwalSelIni = collect($matrixJadwal[$s][$hari])->where('kelas', $namaKelas); 
                                $firstJadwal = $jadwalSelIni->first();
                                
                                $bgSel = 'background-color: #ffffff;';
                                if ($firstJadwal) {
                                    $tailwindWarna = $firstJadwal['warna'] ?? 'bg-blue-50';
                                    $hexWarna = $colorMap[$tailwindWarna] ?? '#ffffff';
                                    $bgSel = "background-color: {$hexWarna}; color: #1f2937;";
                                }
                            @endphp
                            
                            <td style="{{ $bgSel }} border: 1px solid #4b5563; padding: 5px; font-size: 9.5px; vertical-align: top;">
                                @foreach($jadwalSelIni as $jadwal)
                                    <span style="font-weight: bold; color: #000000; font-size: 10px;">{{ $jadwal['mata_kuliah'] }}</span><br>
                                    <span style="color: #374151; font-size: 8.5px;">Dosen: {{ explode(',', $jadwal['dosen'])[0] }}</span><br>
                                    <span style="color: #374151; font-size: 8.5px;">R: {{ $jadwal['ruang'] }} ({{ $jadwal['jenis'] }})</span>
                                    @if(!$loop->last)<br><br>@endif
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endfor

                @php $mandiriKelasIni = $matkulMandiri->where('kelas', $namaKelas); @endphp
                @if($mandiriKelasIni->count() > 0)
                    <tr>
                        <td colspan="6" style="background-color: #f0fdf4; color: #14532d; border: 1px solid #4b5563; padding: 5px; font-size: 10px; font-weight: bold; text-align: left;">
                            Info Kegiatan Mandiri / MBKM:
                        </td>
                    </tr>
                    @foreach($mandiriKelasIni as $mm)
                        @php
                            $tampilDosen = !empty($mm['nama_dosen']) && strtolower($mm['nama_dosen']) !== 'tim' && strtolower($mm['nama_dosen']) !== 'mandiri' && !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                        @endphp
                        <tr>
                            <td colspan="6" style="background-color: #f0fdf4; color: #14532d; border: 1px solid #4b5563; padding: 4px 8px; font-size: 9px; text-align: left;">
                                • {{ $mm['nama_matkul'] }} @if($tampilDosen) ({{ $mm['nama_dosen'] }}) @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
        <table><tr><td colspan="6" style="height: 15px; border: none;"></td></tr></table>
    @endforeach

@else
    {{-- matriks 1 tabel aja --}}
    @include('exports.partials.jadwal-filter-header')

    <table style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th style="font-weight: bold; text-align: center; background-color: #ffffff; color: #000000; font-size: 10px; text-transform: uppercase; border: 1px solid #4b5563; height: 20px; vertical-align: middle;">Sesi</th>
                @foreach($hariKerja as $hari)
                    <th style="font-weight: bold; text-align: center; background-color: #ffffff; color: #000000; font-size: 10px; text-transform: uppercase; border: 1px solid #4b5563; height: 20px; vertical-align: middle;">{{ strtoupper($hari) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for($s = 1; $s <= $totalSesi; $s++)
                <tr>
                    <td style="font-weight: bold; text-align: center; background-color: #f3f4f6; color: #1f2937; border: 1px solid #4b5563; padding: 5px; font-size: 11px; vertical-align: middle;">{{ $s }}</td>
                    @foreach($hariKerja as $hari)
                        @php
                            $firstJadwal = collect($matrixJadwal[$s][$hari])->first();
                            $bgSel = 'background-color: #ffffff;';
                            if ($firstJadwal) {
                                $tailwindWarna = $firstJadwal['warna'] ?? 'bg-blue-50';
                                $hexWarna = $colorMap[$tailwindWarna] ?? '#ffffff';
                                $bgSel = "background-color: {$hexWarna}; color: #1f2937;";
                            }
                        @endphp
                        <td style="{{ $bgSel }} border: 1px solid #4b5563; padding: 5px; font-size: 9.5px; vertical-align: top;">
                            @foreach($matrixJadwal[$s][$hari] as $jadwal)
                                <span style="font-weight: bold; color: #000000; font-size: 10px;">{{ $jadwal['mata_kuliah'] }}</span><br>
                                <span style="color: #374151; font-size: 8.5px;">Dosen: {{ explode(',', $jadwal['dosen'])[0] }}</span><br>
                                <span style="color: #374151; font-size: 8.5px;">Kls: {{ $jadwal['kelas'] }} | R: {{ $jadwal['ruang'] }}</span>
                                @if(!$loop->last)<br><br>@endif
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endfor

            @if($matkulMandiri->count() > 0)
                <tr>
                    <td colspan="6" style="background-color: #f0fdf4; color: #14532d; border: 1px solid #4b5563; padding: 5px; font-size: 10px; font-weight: bold; text-align: left;">
                        Info Kegiatan Mandiri / MBKM:
                    </td>
                </tr>
                @foreach($matkulMandiri as $mm)
                    @php
                        $tampilDosen = !empty($mm['nama_dosen']) && strtolower($mm['nama_dosen']) !== 'tim' && strtolower($mm['nama_dosen']) !== 'mandiri' && !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                    @endphp
                    <tr>
                        <td colspan="6" style="background-color: #f0fdf4; color: #14532d; border: 1px solid #4b5563; padding: 4px 8px; font-size: 9px; text-align: left;">
                            • {{ $mm['nama_matkul'] }} - Kelas {{ $mm['kelas'] }} @if($tampilDosen) ({{ $mm['nama_dosen'] }}) @endif
                        </td>
                    </tr>
                @endforeach
            @endif

        </tbody>
    </table>
@endif