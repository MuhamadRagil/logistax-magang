@extends('layouts.app')

@section('title', 'Riwayat Aktivitas')

@section('content')
<div x-data="{ showDetail: false, detailData: null }">

    <form method="GET" action="{{ route('activity-logs.index') }}" class="bg-white border border-dash-border rounded-xl px-5 py-4.5 mb-4.5 flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-1.5">
            <label class="text-[13px] font-semibold text-dash-slate whitespace-nowrap">Dari</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
        </div>
        <div class="flex items-center gap-1.5">
            <label class="text-[13px] font-semibold text-dash-slate whitespace-nowrap">Sampai</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
        </div>
        <select name="action" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
            <option value="">Semua Aksi</option>
            @foreach ($actions as $key => $label)
                <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="actor_id" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
            <option value="">Semua Admin</option>
            @foreach ($actors as $actor)
                <option value="{{ $actor->id }}" @selected(request('actor_id') === $actor->id)>{{ $actor->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-3.5 py-2.5 border border-dash-border rounded-lg text-[13.5px] font-semibold text-dash-slate bg-white cursor-pointer">Terapkan</button>
        @if (request()->hasAny(['date_from', 'date_to', 'action', 'actor_id']))
            <a href="{{ route('activity-logs.index') }}" class="px-3.5 py-2.5 text-[13.5px] font-semibold text-dash-faint no-underline hover:text-dash-slate">Reset</a>
        @endif
    </form>

    <div class="bg-white border border-dash-border rounded-xl overflow-hidden">
        @if ($logs->isEmpty())
            @include('partials.empty-state', [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'title' => 'Belum ada aktivitas',
                'description' => request()->hasAny(['date_from', 'date_to', 'action', 'actor_id'])
                    ? 'Tidak ada riwayat aktivitas untuk filter ini.'
                    : 'Belum ada riwayat aktivitas.',
                'resetUrl' => request()->hasAny(['date_from', 'date_to', 'action', 'actor_id'])
                    ? route('activity-logs.index')
                    : null,
            ])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-[13.5px]">
                    <thead>
                        <tr class="border-b border-dash-border bg-[#FAFBFC]">
                            <th class="text-left px-5 py-3 font-semibold text-dash-slate whitespace-nowrap">Waktu</th>
                            <th class="text-left px-5 py-3 font-semibold text-dash-slate whitespace-nowrap">Admin</th>
                            <th class="text-left px-5 py-3 font-semibold text-dash-slate whitespace-nowrap">Aksi</th>
                            <th class="text-left px-5 py-3 font-semibold text-dash-slate whitespace-nowrap">Subjek</th>
                            <th class="text-center px-5 py-3 font-semibold text-dash-slate whitespace-nowrap">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr class="border-b border-dash-border last:border-b-0 hover:bg-[#FAFBFC]">
                                <td class="px-5 py-3 text-dash-ink whitespace-nowrap">{{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                                <td class="px-5 py-3 text-dash-ink">{{ $log->actor_name }}</td>
                                <td class="px-5 py-3 text-dash-ink">{{ $actions[$log->action] ?? $log->action }}</td>
                                <td class="px-5 py-3 text-dash-ink">{{ $log->subject_label }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if ($log->metadata)
                                        <button
                                            type="button"
                                            @click="detailData = @js($log->metadata); showDetail = true"
                                            class="px-3 py-1 bg-dash-bg text-dash-slate text-[12px] font-semibold rounded-md border border-dash-border cursor-pointer hover:bg-gray-100"
                                        >Lihat</button>
                                    @else
                                        <span class="text-dash-faint text-[12px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>

    {{-- Detail metadata modal --}}
    <div
        x-show="showDetail"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30"
        @click.self="showDetail = false"
        @keydown.escape.window="showDetail = false"
    >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[15px] font-bold text-dash-ink">Detail Aktivitas</h3>
                <button @click="showDetail = false" class="text-dash-faint hover:text-dash-ink cursor-pointer bg-transparent border-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-2" x-show="detailData">
                <template x-for="(value, key) in detailData" :key="key">
                    <div class="flex gap-3 py-2 border-b border-dash-border last:border-b-0">
                        <span class="text-[13px] font-semibold text-dash-slate min-w-[120px] capitalize" x-text="key.replace(/_/g, ' ')"></span>
                        <span class="text-[13px] text-dash-ink break-all" x-text="typeof value === 'object' ? JSON.stringify(value) : String(value)"></span>
                    </div>
                </template>
            </div>
            <div class="mt-5 flex justify-end">
                <button @click="showDetail = false" class="px-4 py-2 bg-dash-navy text-white border-0 rounded-lg text-[13px] font-bold cursor-pointer">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
