{{--
    Shared empty-state component.

    @param string       $icon        — SVG path(s) for the illustration
    @param string       $title       — Headline text
    @param string       $description — Supporting copy
    @param string|null  $actionUrl   — Optional CTA button URL
    @param string|null  $actionLabel — CTA button label
    @param string|null  $resetUrl    — Optional "Reset Filter" URL
    @param string|null  $viewBox     — Custom viewBox (default "0 0 24 24")
--}}
<div class="py-12 flex flex-col items-center text-center px-4">
    <div class="w-11 h-11 rounded-xl bg-dash-bg flex items-center justify-center mb-3.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5.5 h-5.5 text-dash-faint" viewBox="{{ $viewBox ?? '0 0 24 24' }}" fill="none" stroke="currentColor" stroke-width="1.5">
            {!! $icon !!}
        </svg>
    </div>
    <div class="text-[14px] font-bold text-dash-ink">{{ $title }}</div>
    <div class="text-[13px] text-dash-muted mt-1 max-w-[340px]">{{ $description }}</div>
    @if (!empty($actionUrl) && !empty($actionLabel))
        <a href="{{ $actionUrl }}" class="mt-4 inline-flex items-center gap-2 px-4.5 py-2.5 bg-dash-navy text-white border-0 rounded-lg text-[13px] font-bold no-underline hover:bg-dash-navy-dark">
            {{ $actionLabel }}
        </a>
    @endif
    @if (!empty($resetUrl))
        <a href="{{ $resetUrl }}" class="mt-3 text-[13px] font-semibold text-dash-faint no-underline hover:text-dash-slate">Reset Filter</a>
    @endif
</div>
