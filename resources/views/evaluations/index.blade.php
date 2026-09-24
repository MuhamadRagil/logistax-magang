@extends('layouts.app')

@section('title', 'Penilaian')

@section('content')
@if ($view === 'list')
    <div class="bg-white border border-dash-border rounded-xl overflow-hidden">
        <div class="grid grid-cols-[2.4fr_1.4fr_1.4fr_1.6fr_1fr] px-5 py-3.5 bg-dash-thead border-b border-dash-border text-[11.5px] font-bold text-dash-faint uppercase tracking-wide">
            <div>Nama</div><div>Divisi</div><div>Periode Selesai</div><div>Status Evaluasi</div><div>Aksi</div>
        </div>
        @forelse ($interns as $intern)
            @php
                $hasEval = $intern->evaluation !== null;
            @endphp
            <div class="grid grid-cols-[2.4fr_1.4fr_1.4fr_1.6fr_1fr] px-5 py-3.5 border-b border-dash-border-soft items-center">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full text-white text-xs font-bold flex items-center justify-center flex-shrink-0" style="background:{{ \App\Support\Badge::avatarColor($intern->full_name) }}">{{ \App\Support\Badge::initials($intern->full_name) }}</div>
                    <span class="text-[13.5px] font-bold text-dash-ink">{{ $intern->full_name }}</span>
                </div>
                <div class="text-[13px] text-dash-slate">{{ $intern->division?->name ?? '—' }}</div>
                <div class="text-[13px] text-dash-slate">{{ $intern->end_date->format('d M Y') }}</div>
                <div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full inline-block {{ $hasEval ? 'text-green-700 bg-green-100' : 'text-amber-700 bg-amber-100' }}">
                        {{ $hasEval ? 'Lengkap' : 'Belum Diisi' }}
                    </span>
                </div>
                <div>
                    <a href="{{ route('evaluations.index', ['intern' => $intern->id]) }}" class="border border-dash-border bg-white rounded-md px-3 py-1.5 text-xs font-bold text-dash-navy no-underline inline-block">
                        {{ $hasEval ? 'Lihat' : 'Isi Nilai' }}
                    </a>
                </div>
            </div>
        @empty
            <div class="py-10 text-center text-sm text-dash-muted">Tidak ada intern yang perlu dinilai saat ini.</div>
        @endforelse
    </div>
@else
    <a href="{{ route('evaluations.index') }}" class="border-0 bg-transparent text-dash-navy text-[13.5px] font-bold cursor-pointer mb-4 flex items-center gap-1.5 no-underline w-fit">← Kembali ke daftar</a>

    <div
        x-data="evalForm({
            discipline: {{ $evaluation->discipline_score ?? 0 }},
            performance: {{ $evaluation->performance_score ?? 0 }},
            attitude: {{ $evaluation->attitude_score ?? 0 }},
            communication: {{ $evaluation->communication_score ?? 0 }},
        })"
        class="grid grid-cols-[2fr_1fr] gap-5 items-start"
    >
        <div class="bg-white border border-dash-border rounded-xl p-6.5">
            <div class="flex items-center justify-between mb-1.5">
                <div>
                    <div class="text-[17px] font-extrabold text-dash-ink">{{ $intern->full_name }}</div>
                    <div class="text-[13px] text-dash-muted mt-0.5">{{ $intern->division?->name ?? '—' }} · Selesai {{ $intern->end_date->format('d M Y') }}</div>
                </div>
            </div>

            @if ($readOnlyReason)
                <div class="mt-3.5 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-[12.5px] text-red-700 font-semibold">
                    {{ $readOnlyReason }}
                </div>
            @elseif ($isAdmin && $hasCertificate)
                <div class="mt-3.5 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-[12.5px] text-amber-800 font-semibold">
                    Sertifikat sudah terbit untuk intern ini. Mengubah nilai akan menandai sertifikat perlu digenerate ulang.
                </div>
            @endif

            @if ($canEdit)
                <form method="POST" action="{{ $evaluation ? route('evaluations.update', $evaluation) : route('evaluations.store') }}" class="mt-5.5">
                    @csrf
                    @if ($evaluation)
                        @method('PATCH')
                    @else
                        <input type="hidden" name="intern_id" value="{{ $intern->id }}">
                    @endif

                    <div class="flex flex-col gap-5.5">
                        @foreach ($weights as $key => $weight)
                            <div>
                                <div class="flex justify-between items-baseline mb-2">
                                    <div class="text-sm font-bold text-dash-ink">{{ $labels[$key] }} <span class="text-xs font-semibold text-dash-faint">(bobot {{ $weight }}%)</span></div>
                                    <div class="text-base font-extrabold text-dash-navy" x-text="scores.{{ $key }}"></div>
                                </div>
                                <input
                                    type="range" min="0" max="100" name="{{ $key }}_score"
                                    x-model.number="scores.{{ $key }}"
                                    class="w-full"
                                    style="-webkit-appearance:none; appearance:none; height:6px; border-radius:3px; background:#E4E9F2; outline:none;"
                                >
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <label class="block text-[13px] font-bold text-dash-ink mb-1.5">Komentar</label>
                        <textarea name="comments" placeholder="Catatan kinerja dan perilaku selama magang..." class="w-full box-border p-3 border border-dash-border rounded-lg text-[13.5px] min-h-[90px] outline-none resize-y focus:border-dash-teal">{{ $evaluation->comments ?? '' }}</textarea>
                    </div>

                    <button type="submit" class="mt-5 px-5.5 py-3 bg-dash-navy text-white border-0 rounded-lg text-sm font-bold cursor-pointer hover:bg-dash-navy-dark">Simpan Evaluasi</button>
                </form>
            @endif
        </div>

        <div class="bg-white border border-dash-border rounded-xl p-6.5 sticky top-0">
            <div class="text-[13px] font-bold text-dash-faint uppercase tracking-wide mb-3.5">Ringkasan Nilai</div>
            <div class="text-center py-5">
                <div class="text-[44px] font-extrabold text-dash-navy leading-none" x-text="total"></div>
                <div class="text-[13px] text-dash-muted mt-1">dari 100</div>
                <div class="mt-3.5 inline-block text-xl font-extrabold px-5.5 py-2 rounded-[10px]" :class="gradeClasses()">
                    Grade <span x-text="grade"></span>
                </div>
            </div>
            <div class="flex flex-col gap-2.5 mt-2 border-t border-dash-border-soft pt-4">
                @foreach ($weights as $key => $weight)
                    <div class="flex justify-between text-[12.5px]">
                        <span class="text-dash-muted">{{ $labels[$key] }}</span>
                        <span class="font-bold text-dash-ink"><span x-text="scores.{{ $key }}"></span> × {{ $weight }}%</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 pt-3 border-t border-dash-border-soft text-[11px] text-dash-faint">
                Angka di atas hanya pratinjau langsung di browser — nilai akhir yang tersimpan selalu dihitung ulang di server.
            </div>
        </div>
    </div>

    <script>
    function evalForm(initial) {
        return {
            scores: initial,
            get total() {
                const s = this.scores;
                return Number(((s.discipline * 0.25) + (s.performance * 0.35) + (s.attitude * 0.25) + (s.communication * 0.15)).toFixed(2));
            },
            get grade() {
                const t = this.total;
                if (t >= 85) return 'A';
                if (t >= 70) return 'B';
                if (t >= 55) return 'C';
                return 'D';
            },
            gradeClasses() {
                const map = {
                    A: 'text-green-700 bg-green-100',
                    B: 'text-blue-700 bg-blue-100',
                    C: 'text-amber-700 bg-amber-100',
                    D: 'text-red-700 bg-red-100',
                };
                return map[this.grade];
            }
        }
    }
    </script>
@endif
@endsection
