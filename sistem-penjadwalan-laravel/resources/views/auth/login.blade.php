<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Penjadwalan</title>
    <link rel="icon" href="{{ asset('images/jkblogo1.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg shadow-black/10 border border-gray-100 mb-6 mx-auto">
                <img src="{{ asset('images/jkblogo1.png') }}" 
                    alt="Logo JKB" 
                    class="h-10 w-10 object-contain"
                    style="display: block; margin: auto;">
            </div>
            
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Login Sistem</h1>
            <p class="text-xs font-bold text-gray-500 mt-1 uppercase tracking-widest">Penjadwalan Perkuliahan JKB</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-indigo-900/5 border border-gray-100 overflow-hidden">
            <div class="p-8">
                
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="username" class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-5">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-user text-gray-400 text-sm"></i>
                            </div>
                            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" class="w-full pl-10 border border-gray-300 rounded-xl p-3 text-sm bg-gray-50 hover:bg-white outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition" placeholder="Masukkan username">
                        </div>
                        <x-input-error :messages="$errors->get('username')" class="mt-2 text-[11px] text-red-500 font-bold" />
                    </div>

                    <label for="password" class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-400 text-sm"></i>
                        </div>
                        
                        <input id="password" type="password" name="password" required autocomplete="current-password" 
                            class="w-full pl-10 pr-10 border border-gray-300 rounded-xl p-3 text-sm bg-gray-50 hover:bg-white outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition" 
                            placeholder="••••••••">
                        
                        <button type="button" onclick="togglePassword()" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-amber-600 transition">
                            <i id="eyeIcon" class="fa-solid fa-eye-slash text-sm"></i>
                        </button>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500" name="remember">
                            <span class="ml-2 text-xs font-bold text-gray-500 group-hover:text-gray-700 transition">Ingat Saya</span>
                        </label>
                        
                        {{-- @if (Route::has('password.request'))
                            <a class="text-xs font-bold text-amber-600 hover:text-amber-800 transition" href="{{ route('password.request') }}">
                                Lupa Password?
                            </a>
                        @endif --}}
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full flex justify-center items-center gap-2 px-8 py-3.5 bg-amber-600 text-white rounded-xl text-sm font-bold hover:bg-amber-700 transition shadow-lg shadow-amber-600/30">
                            Masuk Dashboard <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
                <a href="{{ route('welcome') }}" class="text-xs font-bold text-gray-500 hover:text-amber-600 transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Portal Publik
                </a>
            </div>
        </div>
        
    </div>

    <script>
    function togglePassword() {
        const passwordField = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (passwordField.type === "password") {
            passwordField.type = "text";
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        } else {
            passwordField.type = "password";
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        }
    }
</script>
</body>
</html>