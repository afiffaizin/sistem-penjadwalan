@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #374151;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #f59e0b; /* Amber-500 */
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('dosen-matkul.index') }}" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-500 hover:bg-amber-50 hover:text-amber-600 shadow-sm border border-gray-100 transition">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Edit Plotting Dosen</h2>
            <p class="text-gray-500 text-sm mt-1">Ubah relasi pengajaran. Auto-create data baru jika tidak ditemukan.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('dosen-matkul.update', $dosen_matkul->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Tahun Ajar -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Tahun Akademik & Semester <span class="text-red-500">*</span></label>
                <select name="tahun_ajar_id" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500" required>
                    <option value="">-- Pilih Tahun Akademik --</option>
                    @foreach($tahunAjars as $ta)
                        <option value="{{ $ta->id }}" {{ $dosen_matkul->tahun_ajar_id == $ta->id ? 'selected' : '' }}>
                            {{ $ta->tahun }} ({{ ucfirst($ta->semester) }}) {{ $ta->is_active ? ' - [Aktif]' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Dosen -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Dosen Pengampu <span class="text-red-500">*</span></label>
                <select name="dosen_id" id="dosen_select" class="smart-select w-full" required data-placeholder="Ketik nama dosen...">
                    <option value=""></option>
                    @foreach($dosens as $dosen)
                        <option value="{{ $dosen->id }}" {{ $dosen_matkul->dosen_id == $dosen->id ? 'selected' : '' }}>
                            {{ $dosen->nama }} ({{ $dosen->kode_dosen }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Input Dosen Tambahan (Disembunyikan secara default) -->
            <div id="dosen_input_group" class="hidden bg-gray-50 p-4 rounded-xl border border-gray-200">
                <p class="text-sm font-bold text-amber-600 mb-3"><i class="fa-solid fa-circle-info"></i> Detail Dosen Baru</p>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Homebase (Prodi Asal)</label>
                    <select name="homebase_prodi_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="">-- Tidak Ada / Kosong --</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}">{{ $prodi->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Mata Kuliah -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Mata Kuliah <span class="text-red-500">*</span></label>
                <select name="mata_kuliah_id" id="mata_kuliah_select" class="smart-select w-full" required data-placeholder="Ketik nama mata kuliah...">
                    <option value=""></option>
                    @foreach($matkuls as $matkul)
                        <option value="{{ $matkul->id }}" {{ $dosen_matkul->mata_kuliah_id == $matkul->id ? 'selected' : '' }}>
                            {{ $matkul->nama }} ({{ $matkul->sks_total }} SKS)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Input SKS Tambahan (Disembunyikan secara default) -->
            <div id="sks_input_group" class="hidden bg-gray-50 p-4 rounded-xl border border-gray-200">
                <p class="text-sm font-bold text-amber-600 mb-3"><i class="fa-solid fa-circle-info"></i> Detail Mata Kuliah Baru</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">SKS Teori</label>
                        <input type="number" name="sks_teori" id="sks_teori" value="0" min="0" max="6" class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">SKS Praktikum</label>
                        <input type="number" name="sks_praktikum" id="sks_praktikum" value="0" min="0" max="6" class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    </div>
                </div>
            </div>

            <!-- Kelas -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Kelas <span class="text-red-500">*</span></label>
                <select name="kelas_id" class="smart-select w-full" required data-placeholder="Ketik nama kelas...">
                    <option value=""></option>
                    @foreach($kelas as $k)
                        <option value="{{ $k->id }}" {{ $dosen_matkul->kelas_id == $k->id ? 'selected' : '' }}>
                            {{ $k->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                <a href="{{ route('dosen-matkul.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg shadow-md transition flex items-center gap-2">
                    <i class="fa-solid fa-save"></i> Perbarui Plotting
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.smart-select').select2({
            tags: true, // Enable auto-create
            width: '100%',
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') {
                    return null;
                }
                return {
                    id: term,
                    text: term + ' (Buat Baru)',
                    newTag: true 
                }
            }
        });

        // Tampilkan input Homebase jika Dosen baru dibuat
        $('#dosen_select').on('change', function() {
            var val = $(this).val();
            // Jika value bukan angka (bukan ID), berarti user mengetik nama baru
            if (val && isNaN(val)) {
                $('#dosen_input_group').slideDown();
            } else {
                $('#dosen_input_group').slideUp();
            }
        });

        // Tampilkan input SKS jika Matkul baru dibuat
        $('#mata_kuliah_select').on('change', function() {
            var val = $(this).val();
            // Jika value bukan angka (bukan ID), berarti user mengetik nama baru
            if (val && isNaN(val)) {
                $('#sks_input_group').slideDown();
                $('#sks_teori, #sks_praktikum').prop('required', true);
            } else {
                $('#sks_input_group').slideUp();
                $('#sks_teori, #sks_praktikum').prop('required', false);
            }
        });
    });
</script>
@endpush
