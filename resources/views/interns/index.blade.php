@extends('layouts.app')

@section('title', 'Manajemen Intern')

@section('content')
<div x-data="internPage()" x-init="init()">

    <div class="flex gap-1.5 mb-5">
        <a href="{{ route('interns.index', ['tab' => 'list']) }}" class="px-4.5 py-2.5 rounded-t-lg border-0 border-b-[2.5px] {{ $activeTab === 'list' ? 'border-dash-teal text-dash-navy' : 'border-transparent text-dash-faint' }} bg-transparent text-sm font-bold no-underline">
            Daftar Intern
        </a>
        <a href="{{ route('interns.index', ['tab' => 'pending']) }}" class="px-4.5 py-2.5 rounded-t-lg border-0 border-b-[2.5px] {{ $activeTab === 'pending' ? 'border-dash-teal text-dash-navy' : 'border-transparent text-dash-faint' }} bg-transparent text-sm font-bold no-underline flex items-center gap-2">
            Pending Registrasi
            <span class="bg-red-100 text-red-700 text-[11px] font-extrabold px-2 py-px rounded-full">{{ $pendingCount }}</span>
        </a>
    </div>

    @if ($activeTab === 'list')
        <form method="GET" action="{{ route('interns.index') }}" class="bg-white border border-dash-border rounded-xl px-5 py-4.5 mb-4.5 flex items-center gap-3 flex-wrap">
            <input type="hidden" name="tab" value="list">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIM..." class="flex-1 min-w-[200px] px-3.5 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
            <select name="division_id" onchange="this.form.submit()" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                <option value="">Semua Divisi</option>
                @foreach ($divisions as $division)
                    <option value="{{ $division->id }}" @selected(request('division_id') === $division->id)>{{ $division->name }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                <option value="">Semua Status</option>
                @foreach (['pending', 'active', 'completed', 'failed', 'extended', 'rejected'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="institution" onchange="this.form.submit()" class="px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] text-dash-slate outline-none">
                <option value="">Semua Institusi</option>
                @foreach ($institutions as $institution)
                    <option value="{{ $institution }}" @selected(request('institution') === $institution)>{{ $institution }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3.5 py-2.5 border border-dash-border rounded-lg text-[13.5px] font-semibold text-dash-slate bg-white cursor-pointer">Terapkan</button>
            <button type="button" @click="showAdd = true" class="px-4.5 py-2.5 bg-dash-navy text-white border-0 rounded-lg text-[13.5px] font-bold cursor-pointer whitespace-nowrap hover:bg-dash-navy-dark">+ Tambah Intern</button>
        </form>

        <div class="bg-white border border-dash-border rounded-xl overflow-hidden">
            <div class="grid grid-cols-[2.3fr_1.1fr_1.6fr_1.4fr_1.3fr_1.4fr_1fr_0.8fr] px-5 py-3.5 bg-dash-thead border-b border-dash-border text-[11.5px] font-bold text-dash-faint uppercase tracking-wide">
                <div>Nama</div><div>NIM</div><div>Institusi</div><div>Divisi</div><div>Mentor</div><div>Periode</div><div>Status</div><div>Aksi</div>
            </div>
            @forelse ($interns as $intern)
                @php [$statusLabel, $statusColor, $statusBg] = \App\Support\Badge::internStatus($intern->status);@endphp
                <div class="grid grid-cols-[2.3fr_1.1fr_1.6fr_1.4fr_1.3fr_1.4fr_1fr_0.8fr] px-5 py-3.5 border-b border-dash-border-soft items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full text-white text-xs font-bold flex items-center justify-center flex-shrink-0" style="background:{{ \App\Support\Badge::avatarColor($intern->full_name) }}">{{ \App\Support\Badge::initials($intern->full_name) }}</div>
                        <span class="text-[13.5px] font-bold text-dash-ink">{{ $intern->full_name }}</span>
                    </div>
                    <div class="text-[13px] text-dash-slate">{{ $intern->nim }}</div>
                    <div class="text-[13px] text-dash-slate">{{ $intern->institution }}</div>
                    <div class="text-[13px] text-dash-slate">{{ $intern->division?->name ?? '—' }}</div>
                    <div class="text-[13px] text-dash-slate">{{ $intern->mentor?->name ?? '—' }}</div>
                    <div class="text-[12.5px] text-dash-muted">{{ $intern->start_date->format('M Y') }} – {{ $intern->end_date->format('M Y') }}</div>
                    <div><span class="text-xs font-bold {{ $statusColor }} {{ $statusBg }} px-2.5 py-1 rounded-full inline-block">{{ $statusLabel }}</span></div>
                    <div class="flex gap-2">
                        <button type="button" @click="openDetail('{{ $intern->id }}')" class="border border-dash-border bg-white rounded-md px-2.5 py-1.5 text-[11.5px] font-bold text-dash-navy cursor-pointer">Lihat</button>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-sm text-dash-muted">Tidak ada intern yang cocok dengan filter ini.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $interns->links() }}</div>
    @else
        <div class="bg-white border border-dash-border rounded-xl overflow-hidden">
            <div class="grid grid-cols-[2fr_1.1fr_1.6fr_1.4fr_1.4fr_1.4fr] px-5 py-3.5 bg-dash-thead border-b border-dash-border text-[11.5px] font-bold text-dash-faint uppercase tracking-wide">
                <div>Nama</div><div>NIM</div><div>Institusi</div><div>Divisi Diajukan</div><div>Tanggal Daftar</div><div>Aksi</div>
            </div>
            @forelse ($pending as $intern)
                <div class="grid grid-cols-[2fr_1.1fr_1.6fr_1.4fr_1.4fr_1.4fr] px-5 py-3.5 border-b border-dash-border-soft items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full text-white text-xs font-bold flex items-center justify-center flex-shrink-0" style="background:{{ \App\Support\Badge::avatarColor($intern->full_name) }}">{{ \App\Support\Badge::initials($intern->full_name) }}</div>
                        <span class="text-[13.5px] font-bold text-dash-ink">{{ $intern->full_name }}</span>
                    </div>
                    <div class="text-[13px] text-dash-slate">{{ $intern->nim }}</div>
                    <div class="text-[13px] text-dash-slate">{{ $intern->institution }}</div>
                    <div class="text-[13px] text-dash-slate">{{ $intern->division?->name ?? '—' }}</div>
                    <div class="text-[12.5px] text-dash-muted">{{ $intern->created_at->format('d M Y') }}</div>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('interns.approve', $intern) }}" onsubmit="return confirm('Approve pendaftaran {{ $intern->full_name }}?');">
                            @csrf
                            @if (! $intern->division_id)
                                <select name="division_id" required class="border border-dash-border rounded-md text-[11px] px-1 py-1 mr-1">
                                    <option value="">Divisi...</option>
                                    @foreach ($divisions as $division)
                                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @if (! $intern->mentor_id)
                                <select name="mentor_id" required class="border border-dash-border rounded-md text-[11px] px-1 py-1 mr-1">
                                    <option value="">Mentor...</option>
                                    @foreach ($mentors as $mentor)
                                        <option value="{{ $mentor->id }}">{{ $mentor->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <button type="submit" class="border-0 bg-green-100 text-green-700 rounded-md px-3 py-1.5 text-xs font-bold cursor-pointer">Approve</button>
                        </form>
                        <button type="button" @click="openReject('{{ $intern->id }}', '{{ addslashes($intern->full_name) }}')" class="border-0 bg-red-100 text-red-700 rounded-md px-3 py-1.5 text-xs font-bold cursor-pointer">Reject</button>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-sm text-dash-muted">Tidak ada registrasi yang menunggu persetujuan.</div>
            @endforelse
        </div>
    @endif

    {{-- Reject modal --}}
    <div x-show="showReject" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[440px] p-6.5" @click.outside="showReject = false">
            <div class="text-base font-extrabold text-dash-ink">Tolak Registrasi</div>
            <div class="text-[13px] text-dash-muted mt-1">Alasan wajib diisi untuk <span x-text="rejectName"></span>.</div>
            <form method="POST" :action="rejectAction">
                @csrf
                <textarea name="reason" required placeholder="Tulis alasan penolakan..." class="w-full box-border mt-4 p-3 border border-dash-border rounded-lg text-[13.5px] min-h-[90px] outline-none resize-y focus:border-dash-teal"></textarea>
                <div class="flex gap-2.5 mt-4.5 justify-end">
                    <button type="button" @click="showReject = false" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                    <button type="submit" class="px-4.5 py-2.5 rounded-lg border-0 bg-red-600 text-white text-[13.5px] font-bold cursor-pointer">Tolak Registrasi</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add intern modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[560px] max-h-[88vh] overflow-auto p-6.5" @click.outside="showAdd = false">
            <div class="text-base font-extrabold text-dash-ink mb-4.5">Tambah Intern Baru</div>
            <form method="POST" action="{{ route('interns.store') }}">
                @csrf
                <div class="grid grid-cols-2 gap-3.5">
                    <div class="col-span-2">
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Nama Lengkap</label>
                        <input name="full_name" value="{{ old('full_name') }}" required placeholder="Nama intern" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="email@kampus.ac.id" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">NIM</label>
                        <input name="nim" value="{{ old('nim') }}" required placeholder="NIM" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Institusi</label>
                        <input name="institution" value="{{ old('institution') }}" required placeholder="Universitas" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Telepon</label>
                        <input name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Jurusan</label>
                        <input name="major" value="{{ old('major') }}" required placeholder="Program studi" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" required class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Tanggal Selesai</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}" required class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Divisi</label>
                        <select name="division_id" required class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                            <option value="">Pilih divisi</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Mentor</label>
                        <select name="mentor_id" required class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none">
                            <option value="">Pilih mentor</option>
                            @foreach ($mentors as $mentor)
                                <option value="{{ $mentor->id }}">{{ $mentor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if ($errors->any())
                    <div class="mt-3 px-3.5 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[12.5px]">
                        <ul class="list-disc pl-4 m-0">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="flex gap-2.5 mt-5.5 justify-end">
                    <button type="button" @click="showAdd = false" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                    <button type="submit" class="px-4.5 py-2.5 rounded-lg border-0 bg-dash-navy text-white text-[13.5px] font-bold cursor-pointer">Simpan Intern</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Detail modal --}}
    <div x-show="showDetail" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[640px] max-h-[88vh] overflow-auto p-7" @click.outside="showDetail = false" x-show="detail">
            <template x-if="detail">
                <div>
                    <div class="flex items-start justify-between">
                        <div class="flex gap-4">
                            <div class="w-16 h-16 rounded-full bg-dash-navy text-white text-xl font-bold flex items-center justify-center flex-shrink-0" x-text="detail.name.split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase()"></div>
                            <div>
                                <div class="text-lg font-extrabold text-dash-ink" x-text="detail.name"></div>
                                <div class="text-[13px] text-dash-muted mt-0.5" x-text="detail.nim + ' · ' + detail.institution"></div>
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full inline-block mt-2" :class="statusClasses(detail.status)" x-text="statusLabel(detail.status)"></span>
                            </div>
                        </div>
                        <button type="button" @click="showDetail = false" class="border-0 bg-dash-bg w-7.5 h-7.5 rounded-full flex items-center justify-center text-dash-slate cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-5.5 p-4.5 bg-dash-thead rounded-lg">
                        <div><div class="text-[11.5px] font-bold text-dash-faint uppercase">Divisi</div><div class="text-[13.5px] text-dash-ink mt-1 font-semibold" x-text="detail.division"></div></div>
                        <div><div class="text-[11.5px] font-bold text-dash-faint uppercase">Mentor</div><div class="text-[13.5px] text-dash-ink mt-1 font-semibold" x-text="detail.mentor"></div></div>
                        <div><div class="text-[11.5px] font-bold text-dash-faint uppercase">Periode</div><div class="text-[13.5px] text-dash-ink mt-1 font-semibold" x-text="detail.period"></div></div>
                        <div><div class="text-[11.5px] font-bold text-dash-faint uppercase">Jurusan</div><div class="text-[13.5px] text-dash-ink mt-1 font-semibold" x-text="detail.major"></div></div>
                    </div>

                    <template x-if="detail.hasExtension">
                        <div class="mt-3.5 px-4 py-3 bg-violet-100 rounded-lg text-[12.5px] text-violet-700 font-semibold">Intern ini pernah mendapat perpanjangan masa magang.</div>
                    </template>

                    <div class="flex gap-1 mt-5.5 border-b border-dash-border">
                        <button type="button" @click="subTab = 'absensi'" class="px-3.5 py-2.5 border-0 bg-transparent text-[13px] font-bold cursor-pointer" :class="subTab === 'absensi' ? 'text-dash-navy border-b-[2.5px] border-dash-teal' : 'text-dash-faint'">Absensi</button>
                        <button type="button" @click="subTab = 'nilai'" class="px-3.5 py-2.5 border-0 bg-transparent text-[13px] font-bold cursor-pointer" :class="subTab === 'nilai' ? 'text-dash-navy border-b-[2.5px] border-dash-teal' : 'text-dash-faint'">Nilai</button>
                        <button type="button" @click="subTab = 'sertifikat'" class="px-3.5 py-2.5 border-0 bg-transparent text-[13px] font-bold cursor-pointer" :class="subTab === 'sertifikat' ? 'text-dash-navy border-b-[2.5px] border-dash-teal' : 'text-dash-faint'">Sertifikat</button>
                    </div>
                    <div class="py-4 px-1 text-[13px] text-dash-muted">
                        <span x-show="subTab === 'absensi'" x-text="detail.attendanceSummary"></span>
                        <span x-show="subTab === 'nilai'" x-text="detail.evaluationSummary"></span>
                        <span x-show="subTab === 'sertifikat'" x-text="detail.certificateSummary"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function internPage() {
    return {
        showAdd: {{ $openAdd ? 'true' : 'false' }},
        showReject: false,
        rejectName: '',
        rejectAction: '',
        showDetail: false,
        detail: null,
        subTab: 'absensi',
        init() {},
        openReject(id, name) {
            this.rejectName = name;
            this.rejectAction = '{{ url('/interns') }}/' + id + '/reject';
            this.showReject = true;
        },
        openDetail(id) {
            this.showDetail = true;
            this.detail = null;
            fetch('{{ url('/interns') }}/' + id + '/detail')
                .then(r => r.json())
                .then(data => { this.detail = data; this.subTab = 'absensi'; });
        },
        statusMap: {
            pending: ['Pending', 'text-amber-700 bg-amber-100'],
            active: ['Active', 'text-green-700 bg-green-100'],
            completed: ['Completed', 'text-blue-700 bg-blue-100'],
            failed: ['Failed', 'text-red-700 bg-red-100'],
            extended: ['Extended', 'text-violet-700 bg-violet-100'],
            rejected: ['Rejected', 'text-red-700 bg-red-100'],
        },
        statusLabel(s) { return this.statusMap[s] ? this.statusMap[s][0] : s; },
        statusClasses(s) { return this.statusMap[s] ? this.statusMap[s][1] : 'text-dash-slate bg-dash-bg'; },
    }
}
</script>
@endsection
