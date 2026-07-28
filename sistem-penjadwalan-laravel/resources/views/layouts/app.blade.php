<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>SI Penjadwalan</title>
        <link rel="icon" href="{{ asset('images/jkblogo1.png') }}" type="image/png">

        <!-- Fonts -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

        <style>
            body { font-family: 'Inter', sans-serif; }
            ::-webkit-scrollbar { width: 6px; height: 6px; }
            ::-webkit-scrollbar-track { background: #f8fafc; }
            ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
            ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

            /* Select2 Custom Styling */
            .select2-container--default .select2-selection--single {
                height: 42px;
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                background-color: #fff;
                padding: 6px 12px;
                font-size: 0.875rem;
                font-family: 'Inter', sans-serif;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }
            .select2-container--default .select2-selection--single:hover {
                border-color: #f59e0b;
            }
            .select2-container--default.select2-container--focus .select2-selection--single,
            .select2-container--default.select2-container--open .select2-selection--single {
                border-color: #f59e0b;
                box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.3);
                outline: none;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 28px;
                padding-left: 0;
                color: #1f2937;
                font-size: 0.875rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 40px;
                right: 8px;
            }
            .select2-container--default .select2-selection--single .select2-selection__placeholder {
                color: #6b7280;
            }
            .select2-dropdown {
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.08);
                font-family: 'Inter', sans-serif;
                overflow: hidden;
            }
            .select2-container--default .select2-search--dropdown .select2-search__field {
                border: 1px solid #e5e7eb;
                border-radius: 0.375rem;
                padding: 8px 12px;
                font-size: 0.875rem;
                font-family: 'Inter', sans-serif;
                outline: none;
            }
            .select2-container--default .select2-search--dropdown .select2-search__field:focus {
                border-color: #f59e0b;
                box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
            }
            .select2-container--default .select2-results__option {
                padding: 8px 12px;
                font-size: 0.875rem;
            }
            .select2-container--default .select2-results__option--highlighted[aria-selected] {
                background-color: #f59e0b;
                color: #fff;
            }
            .select2-container--default .select2-results__option[aria-selected=true] {
                background-color: #fef3c7;
                color: #92400e;
            }
            .select2-container {
                width: 100% !important;
            }
        </style>
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 text-gray-800 h-screen flex overflow-hidden font-sans">

        <!-- Mobile Sidebar Backdrop -->
        <div id="sidebarBackdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-gray-900/50 z-30 hidden md:hidden transition-opacity"></div>

        @include('layouts.sidebar')

        <div class="flex-1 flex flex-col h-screen overflow-hidden min-w-0">
            
            @include('layouts.navbar')

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 px-4 md:px-8 pb-8 pt-4">
                @yield('content')
            </main>
            
        </div>

        <!-- jQuery + Select2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                if (sidebar && backdrop) {
                    const isHidden = sidebar.classList.contains('-translate-x-full');
                    if (isHidden) {
                        sidebar.classList.remove('-translate-x-full');
                        sidebar.classList.add('translate-x-0');
                        backdrop.classList.remove('hidden');
                    } else {
                        sidebar.classList.remove('translate-x-0');
                        sidebar.classList.add('-translate-x-full');
                        backdrop.classList.add('hidden');
                    }
                }
            }

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    const backdrop = document.getElementById('sidebarBackdrop');
                    if (backdrop && !backdrop.classList.contains('hidden')) {
                        backdrop.classList.add('hidden');
                    }
                }
            });

            $(document).ready(function() {
                $('.select2-filter').each(function() {
                    $(this).select2({
                        placeholder: $(this).find('option[value=""]').text() || 'Pilih...',
                        allowClear: true,
                        width: '100%'
                    });
                });
            });
        </script>
        @stack('scripts')
    </body>
</html>
