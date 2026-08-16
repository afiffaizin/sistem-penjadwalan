@extends('layouts.app')

@section('content')
    @php
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

    <div class="container mx-auto max-w-7xl">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border-l-8 border-amber-600">
            <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight mb-2">Jadwal Kuliah Program Studi</h1>
            <p class="text-gray-500 text-sm mb-6">Jadwal di bawah ini dikelompokkan berdasarkan Kelas pada program studi
                Anda.</p>

            <form action="{{ route('kaprodi.jadwal') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tahun Ajaran</label>
                        <select name="tahun_ajar_id" onchange="document.getElementById('kelas_id_filter').value=''; this.form.submit()"
                            class="select2-filter w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white">
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            @foreach ($tahunAjars as $ta)
                                <option value="{{ $ta->id }}" {{ $selectedTahunAjarId == $ta->id ? 'selected' : '' }}>
                                    {{ $ta->tahun }} - {{ $ta->semester }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Dosen Pengajar</label>
                        <select name="dosen_id"
                            class="select2-filter w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white">
                            <option value="">-- Semua Dosen --</option>
                            @foreach ($dosens as $d)
                                <option value="{{ $d->id }}" {{ request('dosen_id') == $d->id ? 'selected' : '' }}>
                                    {{ $d->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kelas</label>
                        <select name="kelas_id" id="kelas_id_filter"
                            class="select2-filter w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white">
                            <option value="">-- Semua Kelas --</option>
                            @foreach ($kelas as $k)
                                <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>
                                    {{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ruangan</label>
                        <select name="ruang_id"
                            class="select2-filter w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-white">
                            <option value="">-- Semua Ruangan --</option>
                            @foreach ($ruangs as $r)
                                <option value="{{ $r->id }}" {{ request('ruang_id') == $r->id ? 'selected' : '' }}>
                                    {{ $r->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-4 pt-4 border-t border-gray-100">

                    <div class="flex gap-3">
                        <a href="{{ route('jadwal.export.excel', array_merge(request()->all(), ['prodi_id' => auth()->user()->prodi_id, 'tahun_ajar_id' => $selectedTahunAjarId])) }}"
                            class="px-4 py-2.5 bg-green-50 text-green-700 border border-green-200 rounded-lg text-sm font-bold hover:bg-green-100 transition shadow-sm flex items-center">
                            <i class="fa-solid fa-file-excel mr-2"></i> Export Excel
                        </a>

                        <a href="{{ route('jadwal.export.pdf', array_merge(request()->all(), ['prodi_id' => auth()->user()->prodi_id, 'tahun_ajar_id' => $selectedTahunAjarId])) }}"
                            class="px-4 py-2.5 bg-red-50 text-red-700 border border-red-200 rounded-lg text-sm font-bold hover:bg-red-100 transition shadow-sm flex items-center">
                            <i class="fa-solid fa-file-pdf mr-2"></i> Export PDF
                        </a>
                    </div>

                    <div class="flex gap-3">
                        <a href="{{ route('kaprodi.jadwal') }}"
                            class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-lg text-sm font-bold hover:bg-gray-200 transition flex items-center">
                            Reset
                        </a>
                        <button type="submit"
                            class="px-6 py-2.5 bg-amber-600 text-white rounded-lg text-sm font-bold hover:bg-amber-700 transition shadow-sm flex items-center">
                            <i class="fa-solid fa-filter mr-2"></i> Tampilkan Jadwal
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if ($selectedTahunAjarId)
            <div class="flex flex-col gap-8">
                @foreach ($kelas as $k)
                    @if (request()->filled('kelas_id') && request('kelas_id') != $k->id)
                        @continue
                    @endif

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 text-center">
                            <h5 class="font-black text-lg text-gray-800 tracking-wide uppercase">KELAS {{ $k->nama }}</h5>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-center border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider">
                                        <th class="border border-gray-200 py-2 w-10 font-extrabold">Sesi</th>
                                        @foreach ($hariKerja as $hari)
                                            <th class="border border-gray-200 py-2 w-1/5 font-extrabold">{{ $hari }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="text-xs">
                                    @for ($s = 1; $s <= $totalSesi; $s++)
                                        <tr>
                                            <td class="border border-gray-200 py-2 font-extrabold bg-gray-50 text-gray-700">
                                                {{ $s }}</td>
                                            @foreach ($hariKerja as $hari)
                                                @php
                                                    $jadwalSelIni = collect($matrixJadwal[$s][$hari])->where(
                                                        'kelas',
                                                        $k->nama,
                                                    );
                                                @endphp

                                                <td class="border border-gray-200 p-1.5 align-top bg-white">
                                                    <div class="flex flex-col gap-2 h-full">
                                                        @forelse($jadwalSelIni as $jadwal)
                                                            @php
                                                                $hexColor =
                                                                    $colorMap[$jadwal['warna'] ?? 'bg-blue-50'] ??
                                                                    '#eff6ff';
                                                            @endphp
                                                            <div style="background-color: {{ $hexColor }};"
                                                                class="border border-black/10 rounded p-1.5 text-left shadow-sm">
                                                                <div
                                                                    class="font-extrabold text-gray-900 text-[11px] leading-tight mb-1">
                                                                    {{ $jadwal['mata_kuliah'] }}</div>
                                                                <div
                                                                    class="flex flex-col gap-1 mt-1 border-t border-black/10 pt-1">
                                                                    <span
                                                                        class="text-[10px] font-bold text-gray-800 truncate"><i
                                                                            class="fa-solid fa-user-tie opacity-50 mr-0.5"></i>
                                                                        {{ explode(',', $jadwal['dosen'])[0] }}</span>
                                                                    <div
                                                                        class="flex justify-between items-center text-[9px] font-semibold text-gray-700">
                                                                        <span
                                                                            class="bg-white/80 px-1 rounded border border-gray-200 shadow-sm"><i
                                                                                class="fa-solid fa-location-dot opacity-50 mr-0.5"></i>
                                                                            {{ $jadwal['ruang'] }}</span>
                                                                        <span
                                                                            class="px-1 py-0.5 rounded text-white font-bold {{ strtolower($jadwal['jenis']) == 'praktikum' ? 'bg-indigo-600' : 'bg-amber-600' }}">{{ $jadwal['jenis'] }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div
                                                                class="h-full w-full flex items-center justify-center text-gray-200 font-light">
                                                                -</div>
                                                        @endforelse
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        <!-- MATA KULIAH MBKM/MANDIRI -->
                        @php
                            $mbkmKelasIni = $matkulMandiri->where('kelas', $k->nama);
                        @endphp

                        @if ($mbkmKelasIni->isNotEmpty())
                            <div class="px-5 py-3 bg-green-50/50 border-t border-green-100">
                                <p class="font-bold text-green-900 text-xs mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-graduation-cap text-green-600"></i> Info Kegiatan Mandiri / MBKM:
                                </p>
                                <ul class="flex flex-wrap gap-x-6 gap-y-2 text-xs text-gray-700">
                                    @foreach ($mbkmKelasIni as $mm)
                                        @php
                                            $tampilDosen =
                                                !empty($mm['nama_dosen']) &&
                                                strtolower($mm['nama_dosen']) !== 'tim' &&
                                                strtolower($mm['nama_dosen']) !== 'mandiri' &&
                                                !str_contains(strtolower($mm['nama_dosen']), 'koordinator');
                                        @endphp
                                        <li class="flex items-center">
                                            <span class="mr-1.5 text-green-500 font-bold">•</span>
                                            <span class="font-bold text-gray-800">{{ $mm['nama_matkul'] }}</span>
                                            @if ($tampilDosen)
                                                <span
                                                    class="text-green-700 font-semibold ml-1">({{ $mm['nama_dosen'] }})</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
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
@endsection
