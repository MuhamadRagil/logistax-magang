@extends('layouts.app')

@section('title', 'Sertifikat & Laporan')

@section('content')
<div class="flex gap-1.5 mb-5">
    <a href="{{ route('certificates.index', ['tab' => 'cert']) }}" class="px-4.5 py-2.5 rounded-t-lg border-0 border-b-[2.5px] {{ $activeTab === 'cert' ? 'border-dash-teal text-dash-navy' : 'border-transparent text-dash-faint' }} bg-transparent text-sm font-bold no-underline">
        Sertifikat
    </a>
    <a href="{{ route('certificates.index', ['tab' => 'report']) }}" class="px-4.5 py-2.5 rounded-t-lg border-0 border-b-[2.5px] {{ $activeTab === 'report' ? 'border-dash-teal text-dash-navy' : 'border-transparent text-dash-faint' }} bg-transparent text-sm font-bold no-underline">
        Laporan
    </a>
</div>

@if ($activeTab === 'cert')
    <div class="bg-white border border-dash-border rounded-xl overflow-hidden">
        <div class="grid grid-cols-[2.2fr_1.4fr_1.6fr_1.8fr] px-5 py-3.5 bg-dash-thead border-b border-dash-border text-[11.5px] font-bold text-dash-faint uppercase tracking-wide">
            <div>Nama</div><div>Periode Selesai</div><div>No. Sertifikat</div><div>Aksi</div>
        </div>
        @forelse ($interns as $intern)
            @php
                $cert = $intern->certificate;
                $canGenerate = $intern->evaluation
                    && $intern->evaluation->discipline_score !== null
                    && $intern->evaluation->performance_score !== null
                    && $intern->evaluation->attitude_score !== null
                    && $intern->evaluation->communication_score !== null;
            @endphp
            <div class="grid grid-cols-[2.2fr_1.4fr_1.6fr_1.8fr] px-5 py-3.5 border-b border-dash-border-soft items-center">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full text-white text-xs font-bold flex items-center justify-center flex-shrink-0" style="background:{{ \App\Support\Badge::avatarColor($intern->full_name) }}">{{ \App\Support\Badge::initials($intern->full_name) }}</div>
                    <span class="text-[13.5px] font-bold text-dash-ink">{{ $intern->full_name }}</span>
                </div>
                <div class="text-[13px] text-dash-slate">{{ $intern->end_date->format('d M Y') }}</div>
                <div class="text-[12.5px] text-dash-muted">{{ $cert->certificate_number ?? '—' }}</div>
                <div class="flex gap-2 flex-wrap">
                    @if ($cert)
                        <a href="{{ route('certificates.download', $intern) }}" class="border border-dash-border bg-white rounded-md px-3 py-1.5 text-xs font-bold text-dash-navy no-underline">Download</a>
                        @if ($intern->evaluation && $intern->evaluation->needs_certificate_regeneration)
                            <form method="POST" action="{{ route('certificates.regenerate', $intern) }}" onsubmit="return confirm('Nilai berubah sejak sertifikat terbit. Generate ulang sekarang?');">
                                @csrf
                                <button type="submit" class="border border-amber-300 bg-amber-50 rounded-md px-3 py-1.5 text-xs font-bold text-amber-700 cursor-pointer">Regenerate</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('certificates.regenerate', $intern) }}" onsubmit="return confirm('Generate ulang sertifikat ini?');">
                                @csrf
                                <button type="submit" class="border border-dash-border bg-white rounded-md px-3 py-1.5 text-xs font-bold text-dash-slate cursor-pointer">Regenerate</button>
                            </form>
                        @endif
                        <a href="{{ route('certificates.preview', $intern) }}" target="_blank" class="border border-dash-border bg-white rounded-md px-3 py-1.5 text-xs font-bold text-dash-slate no-underline">Preview</a>
                    @elseif ($canGenerate)
                        <a href="{{ route('certificates.preview', $intern) }}" target="_blank" class="border border-dash-border bg-white rounded-md px-3 py-1.5 text-xs font-bold text-dash-slate no-underline">Preview</a>
                        <form method="POST" action="{{ route('certificates.generate', $intern) }}" onsubmit="return confirm('Generate sertifikat untuk {{ addslashes($intern->full_name) }}?');">
                            @csrf
                            <button type="submit" class="border-0 bg-dash-navy rounded-md px-3.5 py-1.5 text-xs font-bold text-white cursor-pointer">Generate Sertifikat</button>
                        </form>
                    @else
                        <span class="text-xs text-dash-faint italic">Evaluasi belum lengkap</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="py-10 text-center text-sm text-dash-muted">Belum ada intern dengan status completed.</div>
        @endforelse
    </div>
@else
    <div class="bg-white border border-dash-border rounded-xl p-6.5 max-w-[520px]">
        <div class="text-[14.5px] font-bold text-dash-ink mb-4.5">Export Laporan</div>
        <form method="GET" action="{{ route('reports.export') }}" class="flex flex-col gap-3.5">
            <div>
                <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Periode</label>
                <div class="flex gap-2.5">
                    <select name="month" class="flex-1 px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected($m === now()->month)>{{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                    <select name="year" class="flex-1 px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                        @foreach (range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}" @selected($y === now()->year)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Jenis Laporan</label>
                <select name="type" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                    <option value="attendance">Absensi</option>
                    <option value="nilai">Nilai</option>
                    <option value="sertifikat">Sertifikat Terbit</option>
                </select>
            </div>
            <button type="submit" class="mt-2 flex items-center justify-center gap-2 px-4 py-3 bg-dash-navy text-white border-0 rounded-lg text-[13.5px] font-bold cursor-pointer hover:bg-dash-navy-dark">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export CSV
            </button>
        </form>
    </div>
@endif
@endsection
