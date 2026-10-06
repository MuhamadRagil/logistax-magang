@php
    $settingsTabs = [
        ['route' => 'settings.office-locations.index', 'match' => 'settings.office-locations.*', 'label' => 'Lokasi Kantor'],
        ['route' => 'settings.divisions.index', 'match' => 'settings.divisions.*', 'label' => 'Divisi'],
        ['route' => 'settings.mentors.index', 'match' => 'settings.mentors.*', 'label' => 'Mentor'],
    ];
@endphp
<div class="flex gap-1.5 mb-5">
    @foreach ($settingsTabs as $tab)
        <a href="{{ route($tab['route']) }}" class="px-4.5 py-2.5 rounded-t-lg border-0 border-b-[2.5px] {{ request()->routeIs($tab['match']) ? 'border-dash-teal text-dash-navy' : 'border-transparent text-dash-faint' }} bg-transparent text-sm font-bold no-underline">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
