<header class="h-20 bg-white flex items-center justify-between px-4 md:px-8 z-10 flex-shrink-0 mb-2 border-b border-gray-100">
    <div class="flex items-center gap-4">
        <button type="button" onclick="toggleSidebar()" class="md:hidden text-gray-500 hover:text-amber-600 focus:outline-none p-2 rounded-lg hover:bg-gray-100 transition" aria-label="Toggle Menu">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
    </div>

    <div class="flex items-center gap-6">
      <div class="relative">
            <button type="button" onclick="toggleProfileMenu()" class="flex items-center gap-3 focus:outline-none">
                <span class="text-sm text-gray-500 font-medium capitalize hidden md:block">
                    {{ auth()->user()->role }} - <span class="font-bold text-gray-800">{{ auth()->user()->nama }}</span>
                </span>
                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->nama) }}&background=f59e0b&color=fff" 
                    alt="Avatar" class="w-8 h-8 rounded-full border border-gray-200 shadow-sm hover:ring-2 hover:ring-amber-500 transition">
            </button>

            <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-600 transition">
                    <i class="fa-solid fa-user-pen mr-2"></i> Edit Profil
                </a>
                <div class="border-t border-gray-100 my-1"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                        <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Logout
                    </button>
                </form>
            </div>
      </div>


    </div>
</header>

<script>
    function toggleProfileMenu() {
        const menu = document.getElementById('profileMenu');
        menu.classList.toggle('hidden');
    }

    window.addEventListener('click', function(e) {
        const menu = document.getElementById('profileMenu');
        const button = document.querySelector('button[onclick="toggleProfileMenu()"]');
        if (!button.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>