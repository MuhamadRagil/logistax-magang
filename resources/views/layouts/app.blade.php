<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Sistem Manajemen Magang</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="m-0 h-screen overflow-hidden bg-[#F5F7FA]" x-data="{ sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1' }" x-init="$watch('sidebarCollapsed', v => localStorage.setItem('sidebarCollapsed', v ? '1' : '0'))">
<div class="flex h-screen w-full overflow-hidden bg-[#F5F7FA]">

    {{-- Sidebar — h-screen + flex-col so the nav (flex-1, its own overflow-y-auto)
         is the only part that ever scrolls; the logo header and Logout button
         stay pinned to the top/bottom of the viewport no matter how tall the
         nav list gets. --}}
    <div
        class="flex-shrink-0 h-screen bg-white border-r border-dash-border flex flex-col py-5 transition-[width] duration-150 ease-in-out"
        :class="sidebarCollapsed ? 'w-[76px] px-2' : 'w-[232px] px-3.5'"
    >
        <div class="flex-shrink-0 flex items-center justify-between px-2 pb-5" :class="sidebarCollapsed && 'justify-center'">
            <img src="{{ asset('images/dashboard-logo.png') }}" alt="Logistax" class="h-6.5 block" x-show="!sidebarCollapsed">
            <button
                @click="sidebarCollapsed = !sidebarCollapsed"
                class="w-7 h-7 rounded-md flex items-center justify-center text-dash-slate hover:bg-dash-bg cursor-pointer border-0 bg-transparent flex-shrink-0"
                :title="sidebarCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" :class="sidebarCollapsed && 'rotate-180'" viewBox="0 0 20 20" fill="none">
                    <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 min-h-0 overflow-y-auto flex flex-col gap-0.5">
            @php
                $navItems = [
                    [
                        'route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'label' => 'Dashboard',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
                    ],
                    [
                        'route' => 'interns.index', 'active' => request()->routeIs('interns.*'), 'label' => 'Manajemen Intern',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
                    ],
                    [
                        'route' => 'attendance.index', 'active' => request()->routeIs('attendance.*'), 'label' => 'Absensi',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-13.5-6l2.5 2.5L14 9.75" />',
                    ],
                    [
                        'route' => 'evaluations.index', 'active' => request()->routeIs('evaluations.*'), 'label' => 'Penilaian',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />',
                    ],
                    [
                        'route' => 'certificates.index', 'active' => request()->routeIs('certificates.*'), 'label' => 'Sertifikat & Laporan',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
                    ],
                ];
            @endphp
            @foreach ($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    class="flex items-center gap-2.5 py-2.5 px-3 rounded-lg no-underline border-l-[3px] {{ $item['active'] ? 'bg-dash-pill border-dash-teal' : 'border-transparent hover:bg-dash-bg' }}"
                    :class="sidebarCollapsed && 'justify-center px-0'"
                    title="{{ $item['label'] }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] flex-shrink-0 {{ $item['active'] ? 'text-dash-navy' : 'text-dash-faint' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        {!! $item['icon'] !!}
                    </svg>
                    <span class="text-sm {{ $item['active'] ? 'font-bold text-dash-navy' : 'font-semibold text-dash-slate' }}" x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="flex-shrink-0 pt-4 border-t border-dash-border">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2.5 py-2.5 px-3 rounded-lg border-0 bg-transparent cursor-pointer hover:bg-dash-bg" :class="sidebarCollapsed && 'justify-center px-0'" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] flex-shrink-0 text-dash-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    <span class="text-sm font-semibold text-dash-slate" x-show="!sidebarCollapsed">Logout</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Main column --}}
    <div class="flex-1 min-w-0 h-screen flex flex-col overflow-hidden">
        {{-- Topbar --}}
        <div class="h-16 flex-shrink-0 bg-white border-b border-dash-border flex items-center justify-between px-7">
            <div class="text-lg font-extrabold text-dash-ink">@yield('title', 'Dashboard')</div>
            <div class="flex items-center gap-4">
                @auth('web')
                    @php($admin = auth('web')->user())
                    <div class="relative cursor-pointer">
                        <div class="w-9 h-9 rounded-full bg-dash-bg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] text-dash-slate" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </div>
                        @if ($hasPendingNotifications ?? false)
                            <div class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-red-600 border-[1.5px] border-white"></div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2.5 border-l border-dash-border pl-4">
                        <div class="w-9 h-9 rounded-full bg-dash-navy text-white flex items-center justify-center text-[13px] font-bold flex-shrink-0">
                            {{ $admin->initials() }}
                        </div>
                        <div>
                            <div class="text-[13.5px] font-bold text-dash-ink leading-tight">{{ $admin->name }}</div>
                            <div class="text-[11px] font-bold text-dash-navy bg-dash-pill px-2 py-px rounded-md inline-block mt-0.5">
                                {{ $admin->roleLabel() }}
                            </div>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        {{-- Page content --}}
        <div class="flex-1 min-h-0 p-7 overflow-auto">
            @if (session('status'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 text-green-700 text-sm font-semibold border border-green-200">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm font-semibold border border-red-200">
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
