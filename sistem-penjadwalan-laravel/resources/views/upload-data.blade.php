@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 mt-2 pb-5 border-b border-gray-100">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Upload Dataset Penjadwalan</h1>
                <p class="text-blue-600 font-medium mt-1 text-sm">Unggah berkas kurikulum aktif untuk memulai proses data
                    cleansing</p>
            </div>

            <div class="relative inline-block text-left shrink-0">
                <button type="button" onclick="toggleTemplateMenu()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition shadow-md shadow-amber-200 text-sm font-semibold focus:outline-none">
                    <i class="fa-solid fa-file-excel text-base"></i>
                    <span>Unduh Template Excel</span>
                    <i id="chevronTemplate" class="fa-solid fa-chevron-down text-xs transition-transform duration-200"></i>
                </button>

                <div id="templateMenu"
                    class="hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-30 origin-top-right">
                    <div class="px-4 py-1.5 border-b border-gray-50 mb-1">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pilih Dataset</p>
                    </div>

                    <a href="{{ asset('templates/dosen_mk.xlsx') }}" download
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition font-medium">
                        <i class="fa-solid fa-cloud-arrow-down text-gray-400"></i> Template Data Dosen
                    </a>

                    <a href="{{ asset('templates/matkul_sks.xlsx') }}" download
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition font-medium">
                        <i class="fa-solid fa-cloud-arrow-down text-gray-400"></i> Template Mata Kuliah
                    </a>

                    <a href="{{ asset('templates/ruang.xlsx') }}" download
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition font-medium">
                        <i class="fa-solid fa-cloud-arrow-down text-gray-400"></i> Template Data Ruangan
                    </a>
                </div>
            </div>
        </div>

        @if (session('error'))
            <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 border border-red-200">
                {!! session('error') !!}
            </div>
        @endif

        <form action="{{ route('upload.proses') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="bg-gray-100 p-6 rounded-lg mb-8 flex flex-col md:flex-row gap-6">
                <div class="flex-1">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Tahun ajar</label>
                    <select name="tahun_ajar"
                        class="w-full border-gray-300 rounded shadow-sm py-2 px-3 focus:ring-amber-500 focus:border-amber-500"
                        required>
                        @php
                            $tahunSekarang = (int) date('Y');
                        @endphp
                        @for ($i = -1; $i <= 2; $i++)
                            @php
                                $tahunMulai = $tahunSekarang + $i;
                                $tahunSelesai = $tahunMulai + 1;
                                $formatTahun = "{$tahunMulai}/{$tahunSelesai}";
                            @endphp
                            <option value="{{ $formatTahun }}" {{ $i === 0 ? 'selected' : '' }}>
                                {{ $formatTahun }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Semester</label>
                    <select name="semester"
                        class="w-full border-gray-300 rounded shadow-sm py-2 px-3 focus:ring-amber-500 focus:border-amber-500"
                        required>
                        <option value="Genap">Genap</option>
                        <option value="Gasal">Gasal</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <label
                    class="border-2 border-dashed border-gray-300 hover:border-amber-500 rounded-xl p-8 text-center cursor-pointer transition bg-gray-50 hover:bg-amber-50 group">
                    <div
                        class="w-12 h-12 bg-white rounded-lg shadow-sm flex items-center justify-center mx-auto mb-4 text-amber-500 text-xl group-hover:scale-110 transition border border-gray-100">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1">dosen_mk.xlsx</h3>
                    <span
                        class="text-[10px] font-bold text-amber-600 bg-amber-100 px-3 py-1 rounded-md mt-2 block w-max mx-auto"><i
                            class="fa-solid fa-upload"></i> Pilih File</span>
                    <input type="file" name="file_dosen" class="mt-4 text-xs w-full" accept=".xlsx,.xls" required>
                </label>

                <label
                    class="border-2 border-dashed border-gray-300 hover:border-amber-500 rounded-xl p-8 text-center cursor-pointer transition bg-gray-50 hover:bg-amber-50 group">
                    <div
                        class="w-12 h-12 bg-white rounded-lg shadow-sm flex items-center justify-center mx-auto mb-4 text-amber-500 text-xl group-hover:scale-110 transition border border-gray-100">
                        <i class="fa-solid fa-table-list"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1">matkul_sks.xlsx</h3>
                    <span
                        class="text-[10px] font-bold text-amber-600 bg-amber-100 px-3 py-1 rounded-md mt-2 block w-max mx-auto"><i
                            class="fa-solid fa-upload"></i> Pilih File</span>
                    <input type="file" name="file_matkul" class="mt-4 text-xs w-full" accept=".xlsx,.xls" required>
                </label>

                <label
                    class="border-2 border-dashed border-gray-300 hover:border-amber-500 rounded-xl p-8 text-center cursor-pointer transition bg-gray-50 hover:bg-amber-50 group">
                    <div
                        class="w-12 h-12 bg-white rounded-lg shadow-sm flex items-center justify-center mx-auto mb-4 text-amber-500 text-xl group-hover:scale-110 transition border border-gray-100">
                        <i class="fa-solid fa-building-user"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1">ruang.xlsx</h3>
                    <span
                        class="text-[10px] font-bold text-amber-600 bg-amber-100 px-3 py-1 rounded-md mt-2 block w-max mx-auto"><i
                            class="fa-solid fa-upload"></i> Pilih File</span>
                    <input type="file" name="file_ruang" class="mt-4 text-xs w-full" accept=".xlsx,.xls" required>
                </label>
            </div>

            <div class="flex justify-center mt-8 border-t border-gray-100 pt-6">
                <button type="submit"
                    class="px-8 py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg shadow-md transition">
                    <i class="fa-solid fa-wand-magic-sparkles mr-2"></i> Upload & Proses Cleansing
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleTemplateMenu() {
            const menu = document.getElementById('templateMenu');
            const chevron = document.getElementById('chevronTemplate');

            menu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        window.addEventListener('click', function(e) {
            const menu = document.getElementById('templateMenu');
            const button = document.querySelector('button[onclick="toggleTemplateMenu()"]');

            if (button && !button.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('hidden');
                document.getElementById('chevronTemplate').classList.remove('rotate-180');
            }
        });
    </script>
@endsection
