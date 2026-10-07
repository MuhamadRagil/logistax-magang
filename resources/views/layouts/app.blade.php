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
    <script>
    (function() {
        const originals = new Map();
        const lock = (btn) => {
            if (originals.has(btn)) return;
            originals.set(btn, { html: btn.innerHTML, disabled: btn.disabled });
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin w-4 h-4 inline-block mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Memproses...';
        };
        const unlock = (btn) => {
            const orig = originals.get(btn);
            if (!orig) return;
            btn.innerHTML = orig.html;
            btn.disabled = orig.disabled;
            originals.delete(btn);
        };
        const unlockAll = () => [...originals.keys()].forEach(unlock);
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName !== 'FORM' || form.method.toUpperCase() === 'GET' || e.defaultPrevented) return;
            const buttons = [...form.querySelectorAll('button[type="submit"]')];
            buttons.forEach(lock);
            // Listeners that run after this one (e.g. confirm() cancelled late) can still cancel the submit.
            setTimeout(() => { if (e.defaultPrevented) buttons.forEach(unlock); }, 0);
        });
        window.addEventListener('pageshow', (e) => { if (e.persisted) unlockAll(); });
    })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="m-0 h-screen overflow-hidden bg-[#F5F7FA]"
      x-data="{
          sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1',
          mobileOpen: false,
          isDesktop: window.matchMedia('(min-width: 1024px)').matches,
          get collapsedView() { return this.sidebarCollapsed && this.isDesktop; },
      }"
      x-init="$watch('sidebarCollapsed', v => localStorage.setItem('sidebarCollapsed', v ? '1' : '0'));
              window.matchMedia('(min-width: 1024px)').addEventListener('change', e => { isDesktop = e.matches; if (e.matches) mobileOpen = false; });"
      @keydown.escape.window="mobileOpen = false">
<div class="flex h-screen w-full overflow-hidden bg-[#F5F7FA]">

    {{-- Mobile drawer overlay (below lg only) --}}
    <div x-show="mobileOpen" x-cloak x-transition.opacity @click="mobileOpen = false" class="fixed inset-0 z-30 bg-[rgba(16,24,40,0.5)] lg:hidden"></div>

    {{-- Sidebar — h-screen + flex-col so the nav (flex-1, its own overflow-y-auto)
         is the only part that ever scrolls; the logo header and Logout button
         stay pinned to the top/bottom of the viewport no matter how tall the
         nav list gets. --}}
    <div
        class="fixed inset-y-0 left-0 z-40 lg:static lg:z-auto flex-shrink-0 h-screen bg-white border-r border-dash-border flex flex-col py-5 transition-[width,transform] duration-150 ease-in-out"
        :class="[mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', collapsedView ? 'w-[76px] px-2' : 'w-[232px] px-3.5']"
    >
        <div class="flex-shrink-0 flex items-center justify-between px-2 pb-5" :class="collapsedView && 'justify-center'">
            <img src="{{ asset('images/dashboard-logo.png') }}" alt="Logistax" class="h-6.5 block" x-show="!collapsedView">
            <button
                type="button"
                @click="mobileOpen = false"
                class="lg:hidden w-8 h-8 rounded-md flex items-center justify-center text-dash-slate hover:bg-dash-bg cursor-pointer border-0 bg-transparent flex-shrink-0"
                aria-label="Tutup menu"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            <button
                @click="sidebarCollapsed = !sidebarCollapsed"
                class="hidden lg:flex w-7 h-7 rounded-md items-center justify-center text-dash-slate hover:bg-dash-bg cursor-pointer border-0 bg-transparent flex-shrink-0"
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

                // admin_magang only — the route itself is also behind
                // admin.role.web:admin_magang, so hiding it here is just UX.
                if (auth('web')->user()?->role === 'admin_magang') {
                    $navItems[] = [
                        'route' => 'activity-logs.index', 'active' => request()->routeIs('activity-logs.*'), 'label' => 'Riwayat Aktivitas',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    ];
                    $navItems[] = [
                        'route' => 'settings.office-locations.index', 'active' => request()->routeIs('settings.*'), 'label' => 'Pengaturan',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
                    ];
                }
            @endphp
            @foreach ($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    class="flex items-center gap-2.5 py-2.5 px-3 rounded-lg no-underline border-l-[3px] {{ $item['active'] ? 'bg-dash-pill border-dash-teal' : 'border-transparent hover:bg-dash-bg' }}"
                    :class="collapsedView && 'justify-center px-0'"
                    @click="mobileOpen = false"
                    title="{{ $item['label'] }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] flex-shrink-0 {{ $item['active'] ? 'text-dash-navy' : 'text-dash-faint' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        {!! $item['icon'] !!}
                    </svg>
                    <span class="text-sm {{ $item['active'] ? 'font-bold text-dash-navy' : 'font-semibold text-dash-slate' }}" x-show="!collapsedView">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="flex-shrink-0 pt-4 border-t border-dash-border">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2.5 py-2.5 px-3 rounded-lg border-0 bg-transparent cursor-pointer hover:bg-dash-bg" :class="collapsedView && 'justify-center px-0'" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] flex-shrink-0 text-dash-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    <span class="text-sm font-semibold text-dash-slate" x-show="!collapsedView">Logout</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Main column --}}
    <div class="flex-1 min-w-0 h-screen flex flex-col overflow-hidden">
        {{-- Topbar --}}
        <div class="h-16 flex-shrink-0 bg-white border-b border-dash-border flex items-center justify-between gap-2 px-4 lg:px-7">
            <div class="flex items-center gap-2 min-w-0">
                <button type="button" @click="mobileOpen = true" class="lg:hidden w-9 h-9 -ml-1 rounded-lg flex items-center justify-center text-dash-slate hover:bg-dash-bg cursor-pointer border-0 bg-transparent flex-shrink-0" aria-label="Buka menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
                </button>
                <div class="text-base lg:text-lg font-extrabold text-dash-ink truncate">@yield('title', 'Dashboard')</div>
            </div>
            <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
                @auth('web')
                    @php($admin = auth('web')->user())
                    <div x-data="bellNotif()" x-init="start()" class="relative" @click.outside="open = false">
                        <button @click="open = !open" class="w-9 h-9 rounded-full bg-dash-bg flex items-center justify-center border-0 cursor-pointer relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] text-dash-slate" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <template x-if="total > 0">
                                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center leading-none" x-text="total > 9 ? '9+' : total"></span>
                            </template>
                        </button>
                        <div x-show="open" x-cloak x-transition class="fixed left-4 right-4 top-[68px] sm:absolute sm:left-auto sm:right-0 sm:top-11 sm:w-[320px] max-h-[70vh] overflow-y-auto bg-white border border-dash-border rounded-xl shadow-lg z-50">
                            <div class="px-4 py-3 border-b border-dash-border">
                                <div class="text-[13px] font-bold text-dash-ink">Notifikasi</div>
                            </div>
                            <template x-if="items.length === 0">
                                <div class="px-4 py-6 text-center text-[13px] text-dash-muted">Tidak ada notifikasi</div>
                            </template>
                            <template x-for="item in items" :key="item.key">
                                <a :href="item.url" class="flex items-center gap-3 px-4 py-3 border-b border-dash-border-soft no-underline hover:bg-dash-bg">
                                    <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0 text-[12px] font-bold" x-text="item.count"></div>
                                    <div class="text-[13px] text-dash-ink" x-text="item.label"></div>
                                </a>
                            </template>
                        </div>
                    </div>
                    <div x-data="{ open: false }" class="relative lg:hidden" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-label="Menu pengguna" class="w-9 h-9 rounded-full bg-dash-navy text-white flex items-center justify-center text-[13px] font-bold border-0 cursor-pointer">{{ $admin->initials() }}</button>
                        <div x-show="open" x-cloak x-transition class="absolute right-0 top-11 w-56 bg-white border border-dash-border rounded-xl shadow-lg z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-dash-border">
                                <div class="text-[13.5px] font-bold text-dash-ink truncate">{{ $admin->name }}</div>
                                <div class="text-[11px] font-bold text-dash-navy bg-dash-pill px-2 py-px rounded-md inline-block mt-1">{{ $admin->roleLabel() }}</div>
                            </div>
                            <a href="{{ route('profile.show') }}" class="block px-4 py-3 text-[13.5px] font-semibold text-dash-ink no-underline hover:bg-dash-bg">Profil Saya</a>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-dash-border-soft">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-[13.5px] font-semibold text-red-600 bg-transparent border-0 cursor-pointer hover:bg-dash-bg">Logout</button>
                            </form>
                        </div>
                    </div>
                    <a href="{{ route('profile.show') }}" class="hidden lg:flex items-center gap-2.5 border-l border-dash-border pl-4 no-underline hover:opacity-80" title="Profil">
                        <div class="w-9 h-9 rounded-full bg-dash-navy text-white flex items-center justify-center text-[13px] font-bold flex-shrink-0">
                            {{ $admin->initials() }}
                        </div>
                        <div>
                            <div class="text-[13.5px] font-bold text-dash-ink leading-tight">{{ $admin->name }}</div>
                            <div class="text-[11px] font-bold text-dash-navy bg-dash-pill px-2 py-px rounded-md inline-block mt-0.5">
                                {{ $admin->roleLabel() }}
                            </div>
                        </div>
                    </a>
                @endauth
            </div>
        </div>

        {{-- Page content --}}
        <div class="flex-1 min-h-0 p-4 lg:p-7 overflow-auto">
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
<script>
function bellNotif() {
    return {
        open: false, total: 0, items: [], _timer: null,
        start() {
            this.fetch();
            this._timer = setInterval(() => { if (!document.hidden) this.fetch(); }, 60000);
        },
        async fetch() {
            try {
                const r = await fetch('{{ route("notifications.counts") }}', { headers: { Accept: 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                this.total = d.total;
                this.items = d.items;
            } catch {}
        },
        destroy() { clearInterval(this._timer); }
    };
}
</script>
</body>
</html>
