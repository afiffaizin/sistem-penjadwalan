@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-7xl">
    <div class="bg-gradient-to-r from-amber-700 to-amber-900 text-white p-8 rounded-2xl shadow-md mb-8">
        <h1 class="text-3xl font-black mb-1">Request Hari Tidak Bisa Mengajar</h1>
        <p class="text-amber-200 text-sm font-medium">Monitoring request Kaprodi yang akan dipakai saat generate jadwal.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <form method="GET" action="{{ route('sekjur.unavailable-days') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Tahun Ajar</label>
                <select name="tahun_ajar_id" class="w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                    <option value="">Semua Tahun Ajar</option>
                    @foreach($tahunAjars as $ta)
                        <option value="{{ $ta->id }}" {{ (string) request('tahun_ajar_id') === (string) $ta->id ? 'selected' : '' }}>
                            {{ $ta->tahun }} - {{ $ta->semester }} {{ $ta->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Prodi</label>
                <select name="prodi_id" class="select2-filter w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                    <option value="">Semua Prodi</option>
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}" {{ (string) request('prodi_id') === (string) $prodi->id ? 'selected' : '' }}>
                            {{ $prodi->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Dosen</label>
                <select name="dosen_id" class="select2-filter w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                    <option value="">Semua Dosen</option>
                    @foreach($dosens as $dosen)
                        <option value="{{ $dosen->id }}" {{ (string) request('dosen_id') === (string) $dosen->id ? 'selected' : '' }}>
                            {{ $dosen->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold transition">
                    Filter
                </button>
                <a href="{{ route('sekjur.unavailable-days') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-bold transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-black text-gray-800">Daftar Request</h2>
            <p class="text-sm text-gray-500 font-medium">Total {{ $requests->count() }} request hari.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4 text-left font-black">Kaprodi</th>
                        <th class="px-6 py-4 text-left font-black">Prodi</th>
                        <th class="px-6 py-4 text-left font-black">Dosen</th>
                        <th class="px-6 py-4 text-left font-black">Tahun Ajar</th>
                        <th class="px-6 py-4 text-left font-black">Hari Tidak Bisa Mengajar</th>
                        <th class="px-6 py-4 text-left font-black">Dibuat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $requestItem)
                        <tr class="hover:bg-amber-50/40 transition">
                            <td class="px-6 py-4 font-bold text-gray-700">{{ $requestItem->user->nama ?? '-' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $requestItem->prodi->nama ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <div class="font-black text-gray-800">{{ $requestItem->dosen->nama ?? '-' }}</div>
                                <div class="text-xs text-gray-400 font-semibold">{{ $requestItem->dosen->nip ?: ($requestItem->dosen->kode_dosen ?: '-') }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $requestItem->tahunAjar->tahun ?? '-' }} - {{ $requestItem->tahunAjar->semester ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-50 text-red-600 font-black text-xs">
                                    {{ $requestItem->hari }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $requestItem->created_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 font-semibold">
                                Belum ada request hari tidak bisa mengajar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
