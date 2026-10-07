{{--
    Shared empty-state component with two variants.

    (a) Truly empty (default): icon, $title, $description and an optional action
        button — either a link ($actionUrl) or an Alpine click ($actionClick),
        both with $actionLabel. Pass them only for roles allowed to act.
    (b) Empty because of an active filter ($filtered = true): fixed
        "Tidak ada hasil untuk filter ini" + "Reset Filter" link ($resetUrl).

    Callers decide $filtered as: filter is active AND unfiltered total > 0.

    @param string       $icon              SVG path(s)
    @param string       $title             Variant (a) headline
    @param string       $description       Variant (a) supporting copy
    @param bool         $filtered          Use variant (b)
    @param string|null  $filteredDescription Variant (b) supporting copy override
    @param string|null  $actionUrl|$actionClick|$actionLabel  Variant (a) button
    @param string|null  $resetUrl          Variant (b) reset link target
--}}
@php
    $filtered = $filtered ?? false;
    $showTitle = $filtered ? 'Tidak ada hasil untuk filter ini' : $title;
    $showDescription = $filtered ? ($filteredDescription ?? 'Coba ubah atau reset filter yang sedang aktif.') : $description;
    $buttonClass = 'mt-4 inline-flex items-center gap-2 px-4.5 py-2.5 bg-dash-navy text-white border-0 rounded-lg text-[13px] font-bold no-underline cursor-pointer hover:bg-dash-navy-dark';
@endphp
<div class="py-12 flex flex-col items-center text-center px-4" data-empty-variant="{{ $filtered ? 'filtered' : 'empty' }}">
    <div class="w-11 h-11 rounded-xl bg-dash-bg flex items-center justify-center mb-3.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5.5 h-5.5 text-dash-faint" viewBox="{{ $viewBox ?? '0 0 24 24' }}" fill="none" stroke="currentColor" stroke-width="1.5">
            {!! $icon !!}
        </svg>
    </div>
    <div class="text-[14px] font-bold text-dash-ink">{{ $showTitle }}</div>
    <div class="text-[13px] text-dash-muted mt-1 max-w-[340px]">{{ $showDescription }}</div>
    @if (! $filtered && ! empty($actionLabel))
        @if (! empty($actionUrl))
            <a href="{{ $actionUrl }}" class="{{ $buttonClass }}">{{ $actionLabel }}</a>
        @elseif (! empty($actionClick))
            <button type="button" @click="{{ $actionClick }}" class="{{ $buttonClass }}">{{ $actionLabel }}</button>
        @endif
    @endif
    @if ($filtered && ! empty($resetUrl))
        <a href="{{ $resetUrl }}" class="mt-3 text-[13px] font-semibold text-dash-navy no-underline hover:underline">Reset Filter</a>
    @endif
</div>
