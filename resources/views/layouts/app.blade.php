<!-- filepath: c:\laragon\www\sales-puterako\resources\views\layouts\app.blade.php -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Puterako Super App</title>

    <link rel="stylesheet" href="//cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <script src="//cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>


    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .sidebar-collapsed {
            width: 4rem !important;
        }

        .sidebar-expanded {
            width: 14rem !important;
        }

        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }

        .menu-label {
            transition: opacity 0.2s, visibility 0.2s;
        }

        .menu-label.hide {
            opacity: 0;
            visibility: hidden;
            width: 0;
            padding: 0;
        }

        .menu-label.show {
            opacity: 1;
            visibility: visible;
            width: auto;
        }

        .menu-item {
            position: relative;
            overflow: visible;
        }

        /* Remove background hover effects */
        .menu-item::before {
            display: none;
        }

        /* Hover effect only on icon and text */
        .menu-item:hover svg {
            color: #008817 !important;
            transform: scale(1.1);
        }

        .menu-item:hover .menu-label span {
            color: #008817;
        }

        /* Active state - only icon and text color change */
        .menu-item.active svg {
            color: #008817 !important;
        }

        .menu-item.active .menu-label span {
            color: #008817;
            font-weight: 600;
        }

        .menu-item.active {
            background: transparent;
        }

        /* Smooth transitions */
        .menu-item svg {
            transition: all 0.2s ease;
        }

        .menu-item .menu-label span {
            transition: color 0.2s ease;
        }

        .sidebar-collapsed .sidebar-header {
            padding: 0.5rem 0.75rem;
        }

        .sidebar-collapsed .sidebar-footer {
            padding: 1rem 0.75rem;
        }

        /* Hide dropdown icon when sidebar collapsed */
        .sidebar-collapsed .dropdown-icon {
            display: none;
        }

        /* Adjust icon container when collapsed */
        .sidebar-collapsed .menu-item>div:first-child {
            margin: 0;
        }

        /* Center icons when sidebar is collapsed */
        .sidebar-collapsed .menu-item {
            justify-content: center;
            margin-left: 6px;
        }

        /* Dropdown Styles */
        .dropdown-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }

        .dropdown-menu.open {
            max-height: 300px;
        }

        .dropdown-icon {
            transition: transform 0.3s ease;
        }

        .dropdown-icon.rotate {
            transform: rotate(180deg);
        }

        .submenu-item {
            position: relative;
            padding-left: 3.5rem;
        }

        /* Submenu hover - only text and icon */
        .submenu-item:hover svg {
            color: #008817 !important;
            transform: scale(1.1);
        }

        .submenu-item:hover span {
            color: #008817;
        }

        .submenu-item:hover::before,
        .submenu-item.active::before {
            display: none;
        }

        /* Submenu active state */
        .submenu-item.active svg {
            color: #008817 !important;
        }

        .submenu-item.active span {
            color: #008817;
            font-weight: 600;
        }

        .submenu-item.active {
            background: transparent;
        }

        /* Submenu transitions */
        .submenu-item svg {
            transition: all 0.2s ease;
        }

        .submenu-item span {
            transition: color 0.2s ease;
        }

        /* Tooltip styles */
        .menu-tooltip {
            position: absolute;
            left: 100%;
            margin-left: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: #1F2937;
            color: white;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            pointer-events: none;
            z-index: 50;
        }

        .sidebar-collapsed .menu-item:hover .menu-tooltip,
        .sidebar-collapsed .menu-item:focus .menu-tooltip {
            opacity: 1;
            visibility: visible;
        }

        /* Adjust button layout when collapsed */
        .sidebar-collapsed button.menu-item {
            position: relative;
        }

        /* User Dropdown Hover Styles */
        .user-dropdown-container:hover .user-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-menu {
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        /* User dropdown arrow rotation on hover */
        .user-dropdown-container:hover .user-dropdown-arrow {
            transform: rotate(180deg);
        }

        .user-dropdown-arrow {
            transition: transform 0.3s ease;
        }
    </style>
</head>

<body class="bg-gray-50">

    <div class="flex flex-col min-h-screen">
        <!-- Navbar -->
        <header
            class="bg-white shadow-sm border-b border-gray-200 w-full p-4 flex justify-between items-center fixed top-0 left-0 z-20"
            style="height:64px;">
            <div class="flex items-center space-x-4">
                <!-- Sidebar Toggle Button -->
                <button id="sidebarToggle"
                    class="p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 transition-colors"
                    type="button">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="flex items-center space-x-2">
                    <img src="{{ asset('assets/puterako_logo.png') }}" alt="Puterako Logo" class="h-5 w-auto">
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <!-- User Profile Dropdown -->
                <div class="relative user-dropdown-container">
                    <button id="userDropdown" type="button"
                        class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none">
                        <!-- User Avatar -->
                        <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </span>
                        </div>

                        <!-- User Info (hidden on mobile) -->
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-400 mt-1">Role: {{ ucfirst(Auth::user()->role ?? 'N/A') }}</p>
                        </div>

                        <!-- Dropdown Arrow -->
                        <svg class="w-4 h-4 text-gray-400 user-dropdown-arrow" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="userDropdownMenu"
                        class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-30 user-dropdown-menu">

                        <!-- User Info in Dropdown -->
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ Auth::user()->name }}</p>
                                    <p class="text-sm text-gray-500">{{ Auth::user()->email }}</p>
                                    <p class="text-xs text-gray-400">Role: {{ ucfirst(Auth::user()->role ?? 'N/A') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Menu Items -->
                        <div class="py-2">
                            <a href="{{ route('profile') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Profile
                            </a>

                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Settings
                            </a>
                        </div>

                        <!-- Logout Button -->
                        <div class="border-t border-gray-100 pt-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1">
                                        </path>
                                    </svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex flex-1 pt-16">
            <!-- Sidebar -->
            <aside id="sidebar"
                class="bg-white border-r border-gray-200 min-h-screen sidebar-expanded sidebar-transition flex flex-col shadow-sm">
                <!-- Sidebar Header -->
                <div class="sidebar-header p-4 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-green-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                        <div class="menu-label show ">
                            <h3 class="font-bold text-gray-900">
                                Puterako Super App - Sales</h3>
                            <p class="text-xs text-gray-500">Puterako ERP</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="flex-1 overflow-y-auto">
                    <div class="space-y-2 p-4">
                        <!-- Dashboard -->
                        <a href="{{ route('dashboard') }}"
                            class="menu-item group flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 relative">
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors flex-shrink-0">
                                <svg class="w-6 h-6 transition-colors text-gray-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                    </path>
                                </svg>
                            </div>
                            <div class="menu-label show">
                                <span class="font-medium transition-colors">Dashboard</span>
                                <p class="text-xs text-gray-500 mt-0.5">Monitoring Dashboard</p>
                            </div>
                            <div class="menu-tooltip">Dashboard</div>
                        </a>

                        <!-- Penawaran (dengan Dropdown) -->
                        <div>
                            <button id="penawaranDropdown" type="button"
                                class="menu-item group flex items-center justify-between w-full px-4 py-3 rounded-xl transition-all duration-200 relative">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors flex-shrink-0">
                                        <svg class="w-6 h-6 transition-colors text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="menu-label show text-left">
                                        <span class="font-medium transition-colors">Penawaran</span>
                                        <p class="text-xs text-gray-500 mt-0.5">Kelola penawaran</p>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 dropdown-icon menu-label show" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                                <div class="menu-tooltip">Penawaran</div>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="penawaranMenu" class="dropdown-menu">
                                <div class="space-y-1 py-2">
                                    <a href="{{ route('penawaran.list') }}"
                                        class="submenu-item block py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 inline-block mr-2 text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <span class="menu-label show text-sm text-gray-700">List Penawaran</span>
                                    </a>
                                    @if(in_array(Auth::user()->role, ['supervisor', 'manager', 'direktur']))
                                    <a href="{{ route('penawaran.approve-list') }}"
                                        class="submenu-item block py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 inline-block mr-2 text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="menu-label show text-sm text-gray-700">Approve List</span>
                                    </a>
                                    @endif
                                    <a href="{{ route('rekap.list') }}"
                                        class="submenu-item block py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 inline-block mr-2 text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <span class="menu-label show text-sm text-gray-700">Rekap Survey</span>
                                    </a>
                                    @if(Auth::user()->role === 'supervisor' || Auth::user()->role === 'administrator')
                                        <a href="{{ route('followup.index') }}"
                                            class="submenu-item block py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                            <x-lucide-phone-outgoing class="w-3 h-3 mr-2 inline text-gray-700" />
                                            <span class="menu-label show text-sm text-gray-700">Atur Follow Up</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Klien (dengan Dropdown) -->
                        <div>
                            <button id="klienDropdown" type="button"
                                class="menu-item group flex items-center justify-between w-full px-4 py-3 rounded-xl transition-all duration-200 relative">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors flex-shrink-0">
                                        <x-lucide-book-user class="w-6 h-6 transition-colors text-gray-600" />
                                    </div>
                                    <div class="menu-label show text-left">
                                        <span class="font-medium transition-colors">Customer</span>
                                        <p class="text-xs text-gray-500 mt-0.5">Kelola customer</p>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 dropdown-icon menu-label show" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                                <div class="menu-tooltip">Klien</div>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="klienMenu" class="dropdown-menu">
                                <div class="space-y-1 py-2">
                                    <a href="{{ route('mitra.list') }}"
                                        class="submenu-item block py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 inline-block mr-2 text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
                                            </path>
                                        </svg>
                                        <span class="menu-label show text-sm text-gray-700">List Customer</span>
                                    </a>
                                    <a href="#"
                                        class="submenu-item block py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 inline-block mr-2 text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                        <span class="menu-label show text-sm text-gray-700">Detail Klien</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- User Management (hanya untuk administrator) -->
                        @if(Auth::user()->role === 'administrator')
                            <div>
                                <button id="userManagementDropdown" type="button"
                                    class="menu-item group flex items-center justify-between w-full px-4 py-3 rounded-xl transition-all duration-200 relative">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors flex-shrink-0">
                                            <x-lucide-user-round-pen name="users"
                                                class="w-6 h-6 transition-colors text-gray-600" />
                                        </div>
                                        <div class="menu-label show text-left">
                                            <span class="font-medium transition-colors">Users</span>
                                            <p class="text-xs text-gray-500 mt-0.5">Kelola user & hak akses</p>
                                        </div>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 dropdown-icon menu-label show" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <div class="menu-tooltip">Users</div>
                                </button>

                                <!-- Dropdown Menu -->
                                <div id="userManagementMenu" class="dropdown-menu">
                                    <div class="space-y-1 py-2">
                                        <a href="{{ route('users.index') }}"
                                            class="submenu-item block py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                            <svg class="w-4 h-4 inline-block mr-2 text-gray-600" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                                </path>
                                            </svg>
                                            <span class="menu-label show text-sm text-gray-700">List Users</span>
                                        </a>
                                        <a href="{{ route('users.permissions') }}"
                                            class="submenu-item block py-2 rounded-lg hover:bg-gray-50 transition-colors">
                                            <svg class="w-4 h-4 inline-block mr-2 text-gray-600" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                                </path>
                                            </svg>
                                            <span class="menu-label show text-sm text-gray-700">Atur Hak Akses</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif


                    </div>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 transition-all duration-300">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const labels = document.querySelectorAll('.menu-label');
        const penawaranDropdown = document.getElementById('penawaranDropdown');
        const penawaranMenu = document.getElementById('penawaranMenu');
        const klienDropdown = document.getElementById('klienDropdown');
        const klienMenu = document.getElementById('klienMenu');
        const penawaranDropdownIcon = penawaranDropdown.querySelector('.dropdown-icon');
        const klienDropdownIcon = klienDropdown.querySelector('.dropdown-icon');
        let expanded = true;
        let penawaranDropdownOpen = false;
        let klienDropdownOpen = false;

        // User Management Dropdown (sidebar)
        const userManagementDropdown = document.getElementById('userManagementDropdown');
        const userManagementMenu = document.getElementById('userManagementMenu');
        const userManagementDropdownIcon = userManagementDropdown ? userManagementDropdown.querySelector('.dropdown-icon') : null;
        let userManagementDropdownOpen = false;

        // User Management Dropdown Toggle (sidebar)
        if (userManagementDropdown) {
            userManagementDropdown.addEventListener('click', function () {
                if (expanded) {
                    userManagementDropdownOpen = !userManagementDropdownOpen;
                    if (userManagementDropdownOpen) {
                        userManagementMenu.classList.add('open');
                        userManagementDropdownIcon.classList.add('rotate');
                    } else {
                        userManagementMenu.classList.remove('open');
                        userManagementDropdownIcon.classList.remove('rotate');
                    }
                }
            });
        }

        // Sidebar Toggle
        toggleBtn.addEventListener('click', function () {
            expanded = !expanded;
            if (expanded) {
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.classList.add('sidebar-expanded');
                labels.forEach(label => {
                    label.classList.remove('hide');
                    label.classList.add('show');
                });
            } else {
                sidebar.classList.remove('sidebar-expanded');
                sidebar.classList.add('sidebar-collapsed');
                labels.forEach(label => {
                    label.classList.remove('show');
                    label.classList.add('hide');
                });
                // Close dropdown when sidebar collapses
                if (penawaranDropdownOpen) {
                    penawaranMenu.classList.remove('open');
                    penawaranDropdownIcon.classList.remove('rotate');
                    penawaranDropdownOpen = false;
                }
                if (klienDropdownOpen) {
                    klienMenu.classList.remove('open');
                    klienDropdownIcon.classList.remove('rotate');
                    klienDropdownOpen = false;
                }
                if (userManagementDropdownOpen && userManagementMenu) {
                    userManagementMenu.classList.remove('open');
                    userManagementDropdownIcon.classList.remove('rotate');
                    userManagementDropdownOpen = false;
                }
            }
        });

        // Penawaran Dropdown Toggle
        penawaranDropdown.addEventListener('click', function () {
            if (expanded) {
                penawaranDropdownOpen = !penawaranDropdownOpen;
                if (penawaranDropdownOpen) {
                    penawaranMenu.classList.add('open');
                    penawaranDropdownIcon.classList.add('rotate');
                } else {
                    penawaranMenu.classList.remove('open');
                    penawaranDropdownIcon.classList.remove('rotate');
                }
            }
        });

        // Klien Dropdown Toggle
        klienDropdown.addEventListener('click', function () {
            if (expanded) {
                klienDropdownOpen = !klienDropdownOpen;
                if (klienDropdownOpen) {
                    klienMenu.classList.add('open');
                    klienDropdownIcon.classList.add('rotate');
                } else {
                    klienMenu.classList.remove('open');
                    klienDropdownIcon.classList.remove('rotate');
                }
            }
        });

        // Set active menu based on current URL
        const currentPath = window.location.pathname;
        const menuItems = document.querySelectorAll('.menu-item, .submenu-item');
        menuItems.forEach(item => {
            if (item.getAttribute('href') === currentPath) {
                item.classList.add('active');
                // If it's a submenu item, open the parent dropdown
                if (item.classList.contains('submenu-item')) {
                    // Check parent dropdown
                    const parentDropdown = item.closest('#penawaranMenu, #klienMenu, #userManagementMenu');
                    if (parentDropdown && parentDropdown.id === 'penawaranMenu') {
                        penawaranMenu.classList.add('open');
                        penawaranDropdownIcon.classList.add('rotate');
                        penawaranDropdownOpen = true;
                    } else if (parentDropdown && parentDropdown.id === 'klienMenu') {
                        klienMenu.classList.add('open');
                        klienDropdownIcon.classList.add('rotate');
                        klienDropdownOpen = true;
                    } else if (parentDropdown && parentDropdown.id === 'userManagementMenu') {
                        userManagementMenu.classList.add('open');
                        userManagementDropdownIcon.classList.add('rotate');
                        userManagementDropdownOpen = true;
                    }
                }
            }
        });

        // Toaster Notyf
       // ===== NOTYF GLOBAL =====
    window.notyf = new Notyf({
        duration: 4000,
        position: { x: 'right', y: 'top' },
        dismissible: true,
        ripple: true,
        types: [
            {
                type: 'warning',
                background: '#f59e0b',
                icon: false
            },
            {
                type: 'info',
                background: '#3b82f6',
                icon: false
            }
        ]
    });

    @if (session('success'))
        window.notyf.success(@json(session('success')));
    @endif

    @if (session('error'))
        window.notyf.error(@json(session('error')));
    @endif

    @if (session('warning'))
        window.notyf.open({
            type: 'warning',
            message: @json(session('warning'))
        });
    @endif

    @if (session('info'))
        window.notyf.open({
            type: 'info',
            message: @json(session('info'))
        });
    @endif

    @if ($errors->any())
        window.notyf.error(@json($errors->first()));
    @endif
    </script>

    @stack('scripts')
</body>

</html>