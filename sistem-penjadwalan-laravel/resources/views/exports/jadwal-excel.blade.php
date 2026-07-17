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
                    <th colspan="6" style="text-align: center; font-weight: bold; font-size: 13px; background-color: #f59e0b; color: #ffffff; height: 28px; border: 1px solid #4b5563; vertical-align: middle;">
                        KELAS {{ strtoupper($namaKelas) }}
                    </th>
                </tr>
                <tr>
                    <th style="font-weight: bold; text-align: center; background-color: #1e3a8a; color: #ffffff; border: 1px solid #4b5563; height: 22px; vertical-align: middle;">Sesi</th>
                    @foreach($hariKerja as $hari)
                        <th style="font-weight: bold; text-align: center; background-color: #1e3a8a; color: #ffffff; border: 1px solid #4b5563; height: 22px; vertical-align: middle;">{{ strtoupper($hari) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($s = 1; $s <= $totalSesi; $s++)
                    <tr>
                        <td style="font-weight: bold; text-align: center; background-color: #f3f4f6; color: #1f2937; border: 1px solid #9ca3af; padding: 6px;">{{ $s }}</td>
                        
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
                            
                            <td style="{{ $bgSel }} border: 1px solid #9ca3af; padding: 6px; font-size: 10px;">
                                @foreach($jadwalSelIni as $jadwal)
                                    <b>{{ $jadwal['mata_kuliah'] }}</b><br>
                                    Dosen: {{ explode(',', $jadwal['dosen'])[0] }}<br>
                                    R: {{ $jadwal['ruang'] }} ({{ $jadwal['jenis'] }})
                                    @if(!$loop->last)<br><br>@endif
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endfor

                @php $mandiriKelasIni = $matkulMandiri->where('kelas', $namaKelas); @endphp
                @if($mandiriKelasIni->count() > 0)
                    <tr>
                        <td colspan="6" style="background-color: #f0fdf4; color: #14532d; border: 1px solid #9ca3af; padding: 6px; font-size: 11px; font-weight: bold; text-align: left;">
                            Info Kegiatan Mandiri / MBKM:
                        </td>
                    </tr>
                    @foreach($mandiriKelasIni as $mm)
                        @php
                            $tampilDosen = !empty($mm['nama_dosen']) && strtolower($mm['nama_dosen']) !== 'tim' && strtolower($mm['nama_dosen']) !== 'mandiri' && !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                        @endphp
                        <tr>
                            <td colspan="6" style="background-color: #f0fdf4; color: #14532d; border: 1px solid #9ca3af; padding: 6px; font-size: 10px; text-align: left;">
                                • {{ $mm['nama_matkul'] }} @if($tampilDosen) ({{ $mm['nama_dosen'] }}) @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
        <table><tr><td></td></tr></table>
    @endforeach

@else
    {{-- matriks 1 tabel aja --}}
    @include('exports.partials.jadwal-filter-header')

    <table style="border-collapse: collapse; width: 1002px;">
        <thead>
            <tr>
                <th style="font-weight: bold; text-align: center; background-color: #1e3a8a; color: #ffffff; border: 1px solid #4b5563; height: 25px; vertical-align: middle;">Sesi</th>
                @foreach($hariKerja as $hari)
                    <th style="font-weight: bold; text-align: center; background-color: #1e3a8a; color: #ffffff; border: 1px solid #4b5563; height: 25px; vertical-align: middle;">{{ strtoupper($hari) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for($s = 1; $s <= $totalSesi; $s++)
                <tr>
                    <td style="font-weight: bold; text-align: center; background-color: #f3f4f6; color: #1f2937; border: 1px solid #9ca3af; padding: 6px;">{{ $s }}</td>
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
                        <td style="{{ $bgSel }} border: 1px solid #9ca3af; padding: 6px; font-size: 10px;">
                            @foreach($matrixJadwal[$s][$hari] as $jadwal)
                                <b>{{ $jadwal['mata_kuliah'] }}</b><br>
                                Dosen: {{ explode(',', $jadwal['dosen'])[0] }}<br>
                                Kls: {{ $jadwal['kelas'] }} | R: {{ $jadwal['ruang'] }}
                                @if(!$loop->last)<br><br>@endif
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endfor

            @if($matkulMandiri->count() > 0)
                <tr>
                    <td colspan="6" style="background-color: #f0fdf4; color: #14532d; border: 1px solid #9ca3af; padding: 6px; font-size: 11px; font-weight: bold; text-align: left;">
                        Info Kegiatan Mandiri / MBKM:
                    </td>
                </tr>
                @foreach($matkulMandiri as $mm)
                    @php
                        $tampilDosen = !empty($mm['nama_dosen']) && strtolower($mm['nama_dosen']) !== 'tim' && strtolower($mm['nama_dosen']) !== 'mandiri' && !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                    @endphp
                    <tr>
                        <td colspan="6" style="background-color: #f0fdf4; color: #14532d; border: 1px solid #9ca3af; padding: 6px; font-size: 10px; text-align: left;">
                            • {{ $mm['nama_matkul'] }} - Kelas {{ $mm['kelas'] }} @if($tampilDosen) ({{ $mm['nama_dosen'] }}) @endif
                        </td>
                    </tr>
                @endforeach
            @endif

        </tbody>
    </table>
@endif