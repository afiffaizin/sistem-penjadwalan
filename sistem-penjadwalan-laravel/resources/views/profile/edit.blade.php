@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Profil Pengguna</h1>
            <p class="text-sm text-gray-500">Kelola informasi akun dan keamanan Anda di sini.</p>
        </div>

        <a href="{{ route('dashboard') }}" 
           class="flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition shadow-sm text-sm font-semibold">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="space-y-6">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
            @include('profile.partials.update-password-form')
        </div>

        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection