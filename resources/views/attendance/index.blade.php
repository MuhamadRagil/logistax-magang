@extends('layouts.app')

@section('title', 'Absensi')

@section('content')
<div x-data="{ showReject: false, rejectAction: '' }">

    <div class="flex gap-1.5 mb-5">
        <a href="{{ route('attendance.index', ['tab' => 'rekap', 'month' => $month, 'year' => $year]) }}" class="px-4.5 py-2.5 rounded-t-lg border-0 border-b-[2.5px] {{ $activeTab === 'rekap' ? 'border-dash-teal text-dash-navy' : 'border-transparent text-dash-faint' }} bg-transparent text-sm font-bold no-underline">
            Rekap Kehadiran
        </a>
        <a href="{{ route('attendance.index', ['tab' => 'approval']) }}" class="px-4.5 py-2.5 rounded-t-lg border-0 border-b-[2.5px] {{ $activeTab === 'approval' ? 'border-dash-teal text-dash-navy' : 'border-transparent text-dash-faint' }} bg-transparent text-sm font-bold no-underline flex items-center gap-2">
            Perlu Approval
            <span class="bg-red-100 text-red-700 text-[11px] font-extrabold px-2 py-px rounded-full">{{ $approvalCount }}</span>
        </a>
    </div>

    @if ($activeTab === 'rekap')
        <form method="GET" action="{{ route('attendance.index') }}" class="bg-white border border-dash-border rounded-xl px-5 py-4 mb-4.5 flex items-center gap-3 flex-wrap">
            <input type="hidden" name="tab" value="rekap">
            <select name="month" onchange="this.form.submit()" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($m === $month)>{{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}</option>
                @endforeach
            </select>
            <select name="year" onchange="this.form.submit()" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                @foreach (range(now()->year - 1, now()->year + 1) as $y)
                    <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                @endforeach
            </select>
            <select name="division_id" onchange="this.form.submit()" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                <option value="">Semua Divisi</option>
                @foreach ($divisions as $division)
                    <option value="{{ $division->id }}" @selected(request('division_id') === $division->id)>{{ $division->name }}</option>
                @endforeach
            </select>
            <select name="intern_id" onchange="this.form.submit()" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                <option value="">Semua Intern</option>
                @foreach ($internOptions as $intern)
                    <option value="{{ $intern->id }}" @selected(request('intern_id') === $intern->id)>{{ $intern->full_name }}</option>
                @endforeach
            </select>
            <div class="ml-auto flex items-center gap-4">
                <div class="flex items-center gap-1.5 text-xs text-dash-muted"><span class="w-2.5 h-2.5 rounded-[3px] bg-green-100 border border-green-300 inline-block"></span>Hadir</div>
                <div class="flex items-center gap-1.5 text-xs text-dash-muted"><span class="w-2.5 h-2.5 rounded-[3px] bg-blue-100 border border-blue-300 inline-block"></span>Izin</div>
                <div class="flex items-center gap-1.5 text-xs text-dash-muted"><span class="w-2.5 h-2.5 rounded-[3px] bg-amber-100 border border-amber-300 inline-block"></span>Sakit</div>
                <div class="flex items-center gap-1.5 text-xs text-dash-muted"><span class="w-2.5 h-2.5 rounded-[3px] bg-red-100 border border-red-300 inline-block"></span>Absen</div>
                <a href="{{ route('attendance.export', request()->query()) }}" class="flex items-center gap-2 px-4 py-2.5 bg-dash-navy text-white border-0 rounded-lg text-[13px] font-bold no-underline whitespace-nowrap hover:bg-dash-navy-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export CSV
                </a>
            </div>
        </form>

        <div class="bg-white border border-dash-border rounded-xl p-5 overflow-x-auto">
            @if (count($rekapRows) === 0)
                <div class="py-8 text-center text-sm text-dash-muted">Tidak ada intern pada filter ini.</div>
            @else
                <div class="flex" style="min-width:{{ 170 + $daysInMonth * 24 }}px;">
                    <div class="w-[170px] flex-shrink-0">
                        <div style="height:26px;"></div>
                        @foreach ($rekapRows as $row)
                            <div style="height:30px;" class="flex items-center text-[13px] font-bold text-dash-ink truncate">{{ $row['name'] }}</div>
                        @endforeach
                    </div>
                    <div class="flex-1 overflow-x-auto">
                        <div class="flex gap-0.5" style="height:26px;">
                            @for ($d = 1; $d <= $daysInMonth; $d++)
                                <div style="width:22px;" class="flex-shrink-0 text-[9.5px] text-dash-faint text-center">{{ $d }}</div>
                            @endfor
                        </div>
                        @foreach ($rekapRows as $row)
                            <div class="flex gap-0.5" style="height:30px;" class="items-center">
                                @foreach ($row['days'] as $day)
                                    <div style="width:22px; height:22px;" class="flex-shrink-0 rounded-[5px] {{ $day['class'] }}"></div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="flex flex-col gap-3.5">
            @forelse ($approvals as $a)
                <div class="bg-white border border-dash-border rounded-xl px-5 py-4.5 flex items-center gap-4.5">
                    <div class="w-15 h-15 rounded-lg flex-shrink-0 flex items-center justify-center text-center overflow-hidden bg-[repeating-linear-gradient(45deg,#F0F2F6,#F0F2F6_6px,#E4E9F2_6px,#E4E9F2_12px)]">
                        @if ($a->proof_file_url)
                            <a href="{{ $a->proof_file_url }}" target="_blank" class="text-[8px] text-dash-navy font-bold no-underline">LIHAT<br>BUKTI</a>
                        @else
                            <span class="text-[8px] text-dash-faint">TANPA<br>BUKTI</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-bold text-dash-ink">{{ $a->intern->full_name ?? '—' }} <span class="font-semibold text-dash-muted text-[12.5px]">· {{ $a->intern?->division?->name ?? '—' }}</span></div>
                        <div class="text-[12.5px] text-dash-muted mt-0.5">Pengajuan <span class="font-bold {{ $a->status === 'sakit' ? 'text-amber-700' : 'text-blue-700' }}">{{ ucfirst($a->status) }}</span> — {{ $a->date->format('d M Y') }}</div>
                        @if ($a->notes)
                            <div class="text-[12.5px] text-dash-faint mt-0.5">"{{ $a->notes }}"</div>
                        @endif
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <form method="POST" action="{{ route('attendance.approve', $a) }}" onsubmit="return confirm('Setujui pengajuan ini?');">
                            @csrf
                            <button type="submit" class="border-0 bg-green-100 text-green-700 rounded-md px-4 py-2 text-[12.5px] font-bold cursor-pointer">Approve</button>
                        </form>
                        <button type="button" @click="showReject = true; rejectAction = '{{ route('attendance.reject', $a) }}'" class="border-0 bg-red-100 text-red-700 rounded-md px-4 py-2 text-[12.5px] font-bold cursor-pointer">Reject</button>
                    </div>
                </div>
            @empty
                <div class="bg-white border border-dash-border rounded-xl py-10 text-center text-sm text-dash-muted">Tidak ada pengajuan yang menunggu approval.</div>
            @endforelse
        </div>
    @endif

    <div x-show="showReject" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[440px] p-6.5" @click.outside="showReject = false">
            <div class="text-base font-extrabold text-dash-ink">Tolak Pengajuan</div>
            <div class="text-[13px] text-dash-muted mt-1">Alasan penolakan akan digabung ke catatan intern.</div>
            <form method="POST" :action="rejectAction">
                @csrf
                <textarea name="reason" required placeholder="Tulis alasan penolakan..." class="w-full box-border mt-4 p-3 border border-dash-border rounded-lg text-[13.5px] min-h-[90px] outline-none resize-y focus:border-dash-teal"></textarea>
                <div class="flex gap-2.5 mt-4.5 justify-end">
                    <button type="button" @click="showReject = false" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                    <button type="submit" class="px-4.5 py-2.5 rounded-lg border-0 bg-red-600 text-white text-[13.5px] font-bold cursor-pointer">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
