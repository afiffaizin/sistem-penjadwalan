<aside id="sidebar" class="w-64 bg-white border-r border-gray-200 flex flex-col h-screen flex-shrink-0 fixed inset-y-0 left-0 z-40 transform -translate-x-full transition-transform duration-300 ease-in-out md:static md:translate-x-0 md:flex shadow-sm">
    
    <div class="h-20 flex items-center justify-between px-6 pt-2">
        <div class="flex items-center">
            <img src="{{ asset('images/jkblogo1.png') }}" alt="Logo" class="h-8 w-8 mr-3">
            <span class="text-md font-bold text-amber-600 tracking-tight">Sistem Penjadwalan Perkuliahan</span>
        </div>
        <button type="button" onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-amber-600 focus:outline-none p-1 transition" aria-label="Close Sidebar">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-2 space-y-1.5">
        @if(auth()->user()->role === 'sekretaris')
            
            <div class="px-4 pb-2 pt-1">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Panel Sekjur</p>
            </div>

            <a href="{{ route('sekjur.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('sekjur.dashboard') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-border-all w-5 text-center text-lg"></i>
                <span class="text-sm">Dashboard</span>
            </a>

            <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('users.*') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-user w-5 text-center text-lg"></i>
                <span class="text-sm">Manajemen User</span>
            </a>

            <a href="{{ route('upload.form') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('upload.*') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-file-arrow-up w-5 text-center text-lg"></i>
                <span class="text-sm">Upload Data</span>
            </a>
            
            <a href="{{ route('cleansing.view') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('cleansing.*') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-broom w-5 text-center text-lg"></i>
                <span class="text-sm">Cleansing</span>
            </a>
            
            <a href="{{ route('jadwal.index') ?? '#' }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('jadwal.index', 'jadwal.generate') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-wand-magic-sparkles w-5 text-center text-lg"></i>
                <span class="text-sm">Generate</span>
            </a>
            
            <a href="{{ route('jadwal.matrix') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('jadwal.matrix') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-regular fa-calendar-days w-5 text-center text-lg"></i>
                <span class="text-sm">Hasil Jadwal</span>
            </a>

            <a href="{{ route('sekjur.unavailable-days') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('sekjur.unavailable-days') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-calendar-xmark w-5 text-center text-lg"></i>
                <span class="text-sm">Request Kaprodi</span>
            </a>

            <div class="relative">
                <button type="button" onclick="toggleMasterData()" class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->is('sekjur/master-data*') || request()->is('master-data*') || request()->is('kelas*','dosen*','prodi*','matkul*','ruang*') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-database w-5 text-center text-lg"></i>
                        <span class="text-sm">Master Data</span>
                    </div>
                    <i id="chevronMasterData" class="fa-solid fa-chevron-down text-xs transition-transform duration-300 {{ request()->is('sekjur/master-data*') || request()->is('master-data*') || request()->is('kelas*','dosen*','prodi*','matkul*','ruang*') ? 'rotate-180' : '' }}"></i>
                </button>

                <div id="submenuMasterData" class="{{ request()->is('sekjur/master-data*') || request()->is('master-data*') || request()->is('kelas*','dosen*','prodi*','matkul*','ruang*') ? 'block' : 'hidden' }} mt-1 space-y-1 pl-12 pr-4 py-2">
                    
                    <a href="{{ route('kelas.index') }}" class="flex items-center gap-2 py-2 text-sm font-medium transition {{ request()->routeIs('kelas.*') ? 'text-amber-600 font-bold' : 'text-gray-500 hover:text-amber-600' }}">
                        <i class="fa-solid fa-users text-xs w-4 text-center"></i>
                        <span>Data Kelas</span>
                    </a>
                    <a href="{{ route('dosen.index') }}" class="flex items-center gap-2 py-2 text-sm font-medium transition {{ request()->routeIs('dosen.*') ? 'text-amber-600 font-bold' : 'text-gray-500 hover:text-amber-600' }}">
                        <i class="fa-solid fa-chalkboard-user text-xs w-4 text-center"></i>
                        <span>Data Dosen</span>
                    </a>
                    <a href="{{ route('prodi.index') }}" class="flex items-center gap-2 py-2 text-sm font-medium transition {{ request()->routeIs('prodi.*') ? 'text-amber-600 font-bold' : 'text-gray-500 hover:text-amber-600' }}">
                        <i class="fa-solid fa-graduation-cap text-xs w-4 text-center"></i>
                        <span>Data Program Studi</span>
                    </a>
                    <a href="{{ route('ruang.index') }}" class="flex items-center gap-2 py-2 text-sm font-medium transition {{ request()->routeIs('ruang.*') ? 'text-amber-600 font-bold' : 'text-gray-500 hover:text-amber-600' }}">
                        <i class="fa-solid fa-door-open text-xs w-4 text-center"></i>
                        <span>Data Ruangan</span>
                    </a>
                    <a href="{{ route('matkul.index') }}" class="flex items-center gap-2 py-2 text-sm font-medium transition {{ request()->routeIs('matkul.*') ? 'text-amber-600 font-bold' : 'text-gray-500 hover:text-amber-600' }}">
                        <i class="fa-solid fa-book text-xs w-4 text-center"></i>
                        <span>Mata Kuliah</span>
                    </a>
                    <a href="{{ route('dosen-matkul.index') }}" class="flex items-center gap-2 py-2 text-sm font-medium transition {{ request()->routeIs('dosen-matkul.*') ? 'text-amber-600 font-bold' : 'text-gray-500 hover:text-amber-600' }}">
                        <i class="fa-solid fa-link text-xs w-4 text-center"></i>
                        <span>Plotting Dosen</span>
                    </a>
                </div>
            </div>

        @elseif(auth()->user()->role === 'kajur')
            
            <div class="px-4 pb-2 pt-1">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Panel Kajur</p>
            </div>

            <a href="{{ route('kajur.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('kajur.dashboard') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-chart-line w-5 text-center text-lg"></i>
                <span class="text-sm">Dashboard Kajur</span>
            </a>

            <a href="{{ route('kajur.jadwal') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('kajur.jadwal') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-table-list w-5 text-center text-lg"></i>
                <span class="text-sm">Monitoring Jadwal</span>
            </a>

        @elseif(auth()->user()->role === 'kaprodi')

            <div class="px-4 pb-2 pt-1">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Panel Kaprodi</p>
            </div>

            <a href="{{ route('kaprodi.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('kaprodi.dashboard') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-chart-pie w-5 text-center text-lg"></i>
                <span class="text-sm">Dashboard Prodi</span>
            </a>

            <a href="{{ route('kaprodi.jadwal') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('kaprodi.jadwal') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-calendar-check w-5 text-center text-lg"></i>
                <span class="text-sm">Jadwal Prodi</span>
            </a>

            <a href="{{ route('kaprodi.unavailable-days') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('kaprodi.unavailable-days', 'kaprodi.unavailable-days.store') ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}">
                <i class="fa-solid fa-calendar-xmark w-5 text-center text-lg"></i>
                <span class="text-sm">Hari Tidak Bisa Mengajar</span>
            </a>

        @endif

    </nav>
    
    <div class="px-4 py-4 border-t border-gray-100 space-y-1">
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-gray-500 hover:bg-gray-100 rounded-lg font-medium transition">
            <i class="fa-solid fa-gear w-5 text-center text-lg"></i>
            <span class="text-sm">Settings Profil</span>
        </a>
        
        <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-red-500 hover:bg-red-50 rounded-lg font-medium transition text-left">
                <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center text-lg"></i>
                <span class="text-sm">Logout</span>
            </button>
        </form>
    </div>
</aside>

<script>
    function toggleMasterData() {
        const submenu = document.getElementById('submenuMasterData');
        const chevron = document.getElementById('chevronMasterData');
        
        if (submenu.classList.contains('hidden')) {
            submenu.classList.remove('hidden');
            submenu.classList.add('block');
            chevron.classList.add('rotate-180');
        } else {
            submenu.classList.remove('block');
            submenu.classList.add('hidden');
            chevron.classList.remove('rotate-180');
        }
    }
</script>