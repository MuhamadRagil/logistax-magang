@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-4 gap-4.5">
    <div class="bg-white border border-dash-border rounded-xl p-5">
        <div class="text-[12.5px] font-semibold text-dash-muted">Total Intern Aktif</div>
        <div class="text-[30px] font-extrabold text-dash-ink mt-2">{{ $statActive }}</div>
        <div class="text-xs text-dash-muted mt-1">{{ $scopeNote }}</div>
    </div>
    <div class="bg-white border border-dash-border rounded-xl p-5">
        <div class="text-[12.5px] font-semibold text-dash-muted">Hadir Hari Ini</div>
        <div class="text-[30px] font-extrabold text-dash-ink mt-2">{{ $statPresentToday }}</div>
        <div class="text-xs text-green-700 font-semibold mt-1">{{ $statPresentPct }}% dari total</div>
    </div>
    <div class="bg-white border border-dash-border rounded-xl p-5">
        <div class="text-[12.5px] font-semibold text-dash-muted">Akan Selesai Bulan Ini</div>
        <div class="text-[30px] font-extrabold text-dash-ink mt-2">{{ $statEndingSoon }}</div>
        <div class="text-xs text-dash-muted mt-1">Perlu evaluasi &amp; sertifikat</div>
    </div>
    <div class="bg-white border border-dash-border rounded-xl p-5 relative">
        <div class="text-[12.5px] font-semibold text-dash-muted">Pending Approval Registrasi</div>
        <div class="flex items-center gap-2.5 mt-2">
            <div class="text-[30px] font-extrabold text-dash-ink">{{ $statPendingApproval }}</div>
            @if ($statPendingApproval > 0)
                <div class="bg-red-100 text-red-700 text-[11px] font-bold px-2.5 py-1 rounded-full">Perlu tindakan</div>
            @endif
        </div>
        <div class="text-xs text-dash-muted mt-1">Registrasi mandiri via mobile app</div>
    </div>
</div>

<div class="grid grid-cols-[2fr_1fr] gap-4.5 mt-5">
    <div class="bg-white border border-dash-border rounded-xl p-5.5">
        <div class="text-[14.5px] font-bold text-dash-ink mb-4.5">Tren Kehadiran — 7 Hari Terakhir</div>
        <div style="height:180px;">
            <canvas id="attendanceTrendChart"></canvas>
        </div>
    </div>
    <div class="bg-white border border-dash-border rounded-xl p-5.5 flex flex-col gap-3">
        <div class="text-[14.5px] font-bold text-dash-ink mb-1.5">Aksi Cepat</div>
        <a href="{{ route('interns.index', ['action' => 'add']) }}" class="w-full flex items-center gap-2.5 px-4 py-3.5 bg-dash-navy text-white rounded-lg text-[13.5px] font-bold no-underline hover:bg-dash-navy-dark">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
            </svg>
            Tambah Intern
        </a>
        <a href="{{ route('interns.index', ['tab' => 'pending']) }}" class="w-full flex items-center gap-2.5 px-4 py-3.5 bg-dash-pill text-dash-navy rounded-lg text-[13.5px] font-bold no-underline hover:bg-dash-pill-hover">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Lihat Pending Registrasi
        </a>
    </div>
</div>

<div class="bg-white border border-dash-border rounded-xl p-5.5 mt-5">
    <div class="text-[14.5px] font-bold text-dash-ink mb-3.5">Intern Perlu Perhatian</div>
    <div class="grid grid-cols-[2fr_1.3fr_2fr_1.2fr] px-1 pb-2.5 border-b border-dash-border text-xs font-bold text-dash-faint uppercase tracking-wide">
        <div>Nama</div><div>Divisi</div><div>Isu</div><div>Tanggal</div>
    </div>
    @forelse ($needAttention as $row)
        <div class="grid grid-cols-[2fr_1.3fr_2fr_1.2fr] px-1 py-3.5 border-b border-dash-border-soft items-center">
            <div class="text-[13.5px] font-bold text-dash-ink">{{ $row['name'] }}</div>
            <div class="text-[13px] text-dash-slate">{{ $row['division'] }}</div>
            <div><span class="text-[12.5px] font-semibold {{ $row['issueColor'] }} {{ $row['issueBg'] }} px-2.5 py-1 rounded-full inline-block w-fit">{{ $row['issue'] }}</span></div>
            <div class="text-[13px] text-dash-muted">{{ $row['dateLabel'] }}</div>
        </div>
    @empty
        <div class="py-8 text-center text-sm text-dash-muted">Tidak ada intern yang perlu perhatian khusus saat ini.</div>
    @endforelse
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const trend = @json($attendanceTrend);
        const ctx = document.getElementById('attendanceTrendChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: trend.map(d => d.label),
                datasets: [{
                    data: trend.map(d => d.heightPct),
                    backgroundColor: (ctx) => {
                        const chart = ctx.chart;
                        const { chartArea } = chart;
                        if (!chartArea) return '#00A6C0';
                        const gradient = chart.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, '#00A6C0');
                        gradient.addColorStop(1, '#003087');
                        return gradient;
                    },
                    borderRadius: { topLeft: 6, topRight: 6 },
                    borderSkipped: false,
                    maxBarThickness: 34,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (item) => trend[item.dataIndex].count + ' hadir (' + item.raw + '%)'
                        }
                    }
                },
                scales: {
                    y: { display: false, min: 0, max: 100 },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#667085', font: { size: 11.5, weight: 600 } }
                    }
                }
            }
        });
    });
</script>
@endsection
