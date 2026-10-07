@extends('layouts.app')

@section('title', 'Manajemen Intern')

@section('content')
<div x-data="internPage({
        openAdd: @js($openAdd),
        rows: @js(($activeTab === 'list' ? $interns : $pending)->map(fn ($i) => ['id' => $i->id, 'name' => $i->full_name])->values()),
        csrfToken: @js(csrf_token()),
        resetPasswordUrlBase: @js(url('/interns')),
        isAdminMagang: @js($isAdminMagang),
    })" x-init="init()">

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
            <button type="button" x-show="selectedIds.length > 0" x-cloak @click="openBulkDelete()" class="flex items-center gap-1.5 px-4 py-2.5 bg-red-600 text-white border-0 rounded-lg text-[13.5px] font-bold cursor-pointer whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Hapus Terpilih (<span x-text="selectedIds.length"></span>)
            </button>
        </form>

        <div class="bg-white border border-dash-border rounded-xl overflow-hidden">
            <div class="grid grid-cols-[28px_2.3fr_1.1fr_1.6fr_1.4fr_1.3fr_1.4fr_1fr_0.8fr] px-5 py-3.5 bg-dash-thead border-b border-dash-border text-[11.5px] font-bold text-dash-faint uppercase tracking-wide items-center">
                <input type="checkbox" :checked="allSelected()" @change="toggleSelectAll($event.target.checked)" class="w-3.5 h-3.5 cursor-pointer" aria-label="Pilih semua">
                <div>Nama</div><div>NIM</div><div>Institusi</div><div>Divisi</div><div>Mentor</div><div>Periode</div><div>Status</div><div>Aksi</div>
            </div>
            @forelse ($interns as $intern)
                @php [$statusLabel, $statusColor, $statusBg] = \App\Support\Badge::internStatus($intern->status);@endphp
                <div class="grid grid-cols-[28px_2.3fr_1.1fr_1.6fr_1.4fr_1.3fr_1.4fr_1fr_0.8fr] px-5 py-3.5 border-b border-dash-border-soft items-center">
                    <input type="checkbox" :checked="selectedIds.includes('{{ $intern->id }}')" @change="toggleOne('{{ $intern->id }}', $event.target.checked)" class="w-3.5 h-3.5 cursor-pointer" aria-label="Pilih {{ $intern->full_name }}">
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
                        @if ($intern->intern_account_id)
                            <button type="button" @click="openResetPassword('{{ $intern->id }}', '{{ addslashes($intern->full_name) }}')" title="Reset Password" class="border border-dash-border bg-white rounded-md w-8 h-8 flex items-center justify-center text-dash-slate cursor-pointer flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.412-.082-.848-.028-1.147.27l-4.286 4.286a2.25 2.25 0 01-1.591.659h-1.5a1.5 1.5 0 01-1.5-1.5v-1.5c0-.597.237-1.17.659-1.591l4.286-4.286c.298-.299.352-.735.27-1.147A6 6 0 1121.75 8.25z" />
                                </svg>
                            </button>
                        @else
                            <button type="button" disabled title="Intern belum memiliki akun login" class="border border-dash-border-soft bg-dash-bg rounded-md w-8 h-8 flex items-center justify-center text-dash-faint cursor-not-allowed flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.412-.082-.848-.028-1.147.27l-4.286 4.286a2.25 2.25 0 01-1.591.659h-1.5a1.5 1.5 0 01-1.5-1.5v-1.5c0-.597.237-1.17.659-1.591l4.286-4.286c.298-.299.352-.735.27-1.147A6 6 0 1121.75 8.25z" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                @include('partials.empty-state', [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />',
                    'title' => 'Tidak ada hasil',
                    'description' => 'Tidak ada intern yang cocok dengan filter ini.',
                    'resetUrl' => route('interns.index', ['tab' => 'list']),
                ])
            @endforelse
        </div>

        <div class="mt-4">{{ $interns->links() }}</div>
    @else
        <div class="flex justify-end mb-3.5" x-show="selectedIds.length > 0" x-cloak>
            <button type="button" @click="openBulkDelete()" class="flex items-center gap-1.5 px-4 py-2.5 bg-red-600 text-white border-0 rounded-lg text-[13.5px] font-bold cursor-pointer whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Hapus Terpilih (<span x-text="selectedIds.length"></span>)
            </button>
        </div>

        <div class="bg-white border border-dash-border rounded-xl overflow-hidden">
            <div class="grid grid-cols-[28px_2fr_1.1fr_1.6fr_1.4fr_1.4fr_1.4fr] px-5 py-3.5 bg-dash-thead border-b border-dash-border text-[11.5px] font-bold text-dash-faint uppercase tracking-wide items-center">
                <input type="checkbox" :checked="allSelected()" @change="toggleSelectAll($event.target.checked)" class="w-3.5 h-3.5 cursor-pointer" aria-label="Pilih semua">
                <div>Nama</div><div>NIM</div><div>Institusi</div><div>Divisi Diajukan</div><div>Tanggal Daftar</div><div>Aksi</div>
            </div>
            @forelse ($pending as $intern)
                <div class="grid grid-cols-[28px_2fr_1.1fr_1.6fr_1.4fr_1.4fr_1.4fr] px-5 py-3.5 border-b border-dash-border-soft items-center">
                    <input type="checkbox" :checked="selectedIds.includes('{{ $intern->id }}')" @change="toggleOne('{{ $intern->id }}', $event.target.checked)" class="w-3.5 h-3.5 cursor-pointer" aria-label="Pilih {{ $intern->full_name }}">
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
                        @if ($intern->intern_account_id)
                            <button type="button" @click="openResetPassword('{{ $intern->id }}', '{{ addslashes($intern->full_name) }}')" title="Reset Password" class="border border-dash-border bg-white rounded-md w-8 h-8 flex items-center justify-center text-dash-slate cursor-pointer flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.412-.082-.848-.028-1.147.27l-4.286 4.286a2.25 2.25 0 01-1.591.659h-1.5a1.5 1.5 0 01-1.5-1.5v-1.5c0-.597.237-1.17.659-1.591l4.286-4.286c.298-.299.352-.735.27-1.147A6 6 0 1121.75 8.25z" />
                                </svg>
                            </button>
                        @else
                            <button type="button" disabled title="Intern belum memiliki akun login" class="border border-dash-border-soft bg-dash-bg rounded-md w-8 h-8 flex items-center justify-center text-dash-faint cursor-not-allowed flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.412-.082-.848-.028-1.147.27l-4.286 4.286a2.25 2.25 0 01-1.591.659h-1.5a1.5 1.5 0 01-1.5-1.5v-1.5c0-.597.237-1.17.659-1.591l4.286-4.286c.298-.299.352-.735.27-1.147A6 6 0 1121.75 8.25z" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                @include('partials.empty-state', [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    'title' => 'Tidak ada pending',
                    'description' => 'Tidak ada registrasi yang menunggu persetujuan.',
                ])
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

    {{-- Bulk delete modal --}}
    <div x-show="showBulkDelete" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[480px] max-h-[88vh] overflow-auto p-6.5" @click.outside="closeBulkDelete()">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="text-base font-extrabold text-dash-ink">Hapus Intern Terpilih</div>
            </div>

            <div class="mt-4 px-3.5 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[12.5px] font-semibold leading-relaxed">
                Tindakan ini PERMANEN. Semua data absensi, evaluasi, dan sertifikat (termasuk file PDF) milik intern yang dipilih akan ikut terhapus dan TIDAK BISA dipulihkan.
            </div>

            <div class="mt-4 text-[12.5px] font-bold text-dash-ink">Intern yang akan dihapus (<span x-text="selectedIds.length"></span>):</div>
            <ul class="mt-1.5 max-h-[160px] overflow-auto text-[13px] text-dash-slate list-disc pl-5 space-y-0.5">
                <template x-for="name in selectedNames()" :key="name">
                    <li x-text="name"></li>
                </template>
            </ul>

            <form method="POST" :action="'{{ route('interns.bulk-delete') }}'" @submit="bulkDeleteSubmitting = true">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="intern_ids[]" :value="id">
                </template>

                <label class="block text-[12.5px] font-bold text-dash-ink mt-4.5 mb-1.5">
                    Ketik <span class="font-mono bg-dash-bg px-1.5 py-0.5 rounded">HAPUS</span> untuk konfirmasi
                </label>
                <input type="text" x-model="confirmText" autocomplete="off" placeholder="HAPUS" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-red-400">

                <div class="flex gap-2.5 mt-5.5 justify-end">
                    <button type="button" @click="closeBulkDelete()" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                    <button type="submit" :disabled="confirmText !== 'HAPUS' || bulkDeleteSubmitting" class="px-4.5 py-2.5 rounded-lg border-0 bg-red-600 text-white text-[13.5px] font-bold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">Hapus Permanen</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reset password modal --}}
    <div x-show="showResetPassword" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[440px] p-6.5" @click.outside="closeResetPassword()">
            <template x-if="!resetResultPassword">
                <div>
                    <div class="text-base font-extrabold text-dash-ink">Reset Password <span x-text="resetInternName"></span></div>

                    <div class="flex gap-2 mt-4.5">
                        <button type="button" @click="resetMode = 'random'" class="flex-1 px-3 py-2.5 rounded-lg border text-[13px] font-bold cursor-pointer" :class="resetMode === 'random' ? 'border-dash-teal bg-dash-pill text-dash-navy' : 'border-dash-border bg-white text-dash-slate'">Generate Otomatis</button>
                        <button type="button" @click="resetMode = 'custom'" class="flex-1 px-3 py-2.5 rounded-lg border text-[13px] font-bold cursor-pointer" :class="resetMode === 'custom' ? 'border-dash-teal bg-dash-pill text-dash-navy' : 'border-dash-border bg-white text-dash-slate'">Tentukan Sendiri</button>
                    </div>

                    <div x-show="resetMode === 'custom'" x-cloak class="mt-4">
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Password Baru</label>
                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" x-model="resetCustomPassword" placeholder="Minimal 8 karakter" autocomplete="new-password" class="w-full box-border px-3.5 py-2.5 pr-11 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                            <button type="button" @click="show = !show" class="absolute right-2.5 top-1/2 -translate-y-1/2 bg-transparent border-0 cursor-pointer p-1 flex items-center justify-center">
                                <span class="w-5 h-5 rounded-full border-[1.5px] border-dash-faint relative block">
                                    <span class="w-2 h-2 rounded-full absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" :class="show ? 'bg-dash-teal' : 'bg-dash-faint'"></span>
                                </span>
                            </button>
                        </div>
                        <div class="text-[11.5px] text-dash-muted mt-1">Minimal 8 karakter.</div>
                    </div>

                    <div x-show="resetError" x-cloak x-text="resetError" class="mt-3.5 px-3.5 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[12.5px] font-semibold"></div>

                    <div class="flex gap-2.5 mt-5.5 justify-end">
                        <button type="button" @click="closeResetPassword()" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                        <button type="button" @click="submitResetPassword()" :disabled="resetSubmitting || (resetMode === 'custom' && resetCustomPassword.length < 8)" class="px-4.5 py-2.5 rounded-lg border-0 bg-dash-navy text-white text-[13.5px] font-bold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                            <span x-text="resetSubmitting ? 'Memproses...' : 'Reset Password'"></span>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="resetResultPassword">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="text-base font-extrabold text-dash-ink">Password Berhasil Direset</div>
                    </div>

                    <div class="mt-4.5 flex items-center gap-2 px-3.5 py-3 border border-dash-border rounded-lg bg-dash-bg">
                        <span class="flex-1 font-mono text-[15px] font-bold text-dash-ink tracking-wide break-all" x-text="resetResultPassword"></span>
                        <button type="button" @click="copyResetPassword()" class="flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-dash-border bg-white text-dash-navy text-[12px] font-bold cursor-pointer flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                            </svg>
                            <span x-text="resetCopied ? 'Tersalin!' : 'Copy'"></span>
                        </button>
                    </div>

                    <div class="mt-3.5 px-3.5 py-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[12.5px] font-semibold leading-relaxed">
                        Password ini hanya ditampilkan sekali. Catat atau salin sekarang, dan segera berikan ke intern yang bersangkutan.
                    </div>

                    <div class="flex justify-end mt-5.5">
                        <button type="button" @click="closeResetPassword()" class="px-4.5 py-2.5 rounded-lg border-0 bg-dash-navy text-white text-[13.5px] font-bold cursor-pointer">Tutup</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Extend modal --}}
    <div x-show="showExtend" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[440px] p-6.5" @click.outside="showExtend = false">
            <div class="text-base font-extrabold text-dash-ink">Perpanjang Masa Magang</div>
            <div class="text-[13px] text-dash-muted mt-1">Perpanjang masa magang <span class="font-bold text-dash-ink" x-text="detail?.name"></span></div>
            <form method="POST" :action="detail ? '{{ url('/interns') }}/' + detail.id + '/extend' : ''">
                @csrf
                <div class="mt-4">
                    <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Tanggal Selesai Baru</label>
                    <input type="date" name="new_end_date" x-model="extendDate" :min="extendMinDate" required class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                </div>
                <div class="mt-3.5">
                    <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Alasan Perpanjangan</label>
                    <textarea name="reason" x-model="extendReason" required placeholder="Minimal 10 karakter..." class="w-full box-border p-3 border border-dash-border rounded-lg text-[13.5px] min-h-[90px] outline-none resize-y focus:border-dash-teal"></textarea>
                    <div class="text-[11.5px] text-dash-muted mt-1">Minimal 10 karakter.</div>
                </div>
                <div class="flex gap-2.5 mt-4.5 justify-end">
                    <button type="button" @click="showExtend = false" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                    <button type="submit" :disabled="extendReason.length < 10 || !extendDate" class="px-4.5 py-2.5 rounded-lg border-0 bg-violet-600 text-white text-[13.5px] font-bold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">Perpanjang</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Mark completed modal --}}
    <div x-show="showMarkCompleted" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[440px] p-6.5" @click.outside="showMarkCompleted = false">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-base font-extrabold text-dash-ink">Tandai Selesai</div>
            </div>
            <div class="mt-4 text-[13.5px] text-dash-slate leading-relaxed">
                Yakin ingin menandai <span class="font-bold text-dash-ink" x-text="detail?.name"></span> sebagai <span class="font-bold text-blue-700">SELESAI</span> magang? Ini mengizinkan evaluasi &amp; sertifikat diproses.
            </div>
            <form method="POST" :action="detail ? '{{ url('/interns') }}/' + detail.id + '/mark-completed' : ''">
                @csrf
                <div class="flex gap-2.5 mt-5.5 justify-end">
                    <button type="button" @click="showMarkCompleted = false" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                    <button type="submit" class="px-4.5 py-2.5 rounded-lg border-0 bg-blue-600 text-white text-[13.5px] font-bold cursor-pointer">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Mark failed modal --}}
    <div x-show="showMarkFailed" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[440px] p-6.5" @click.outside="showMarkFailed = false">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-base font-extrabold text-dash-ink">Tandai Gagal</div>
            </div>
            <div class="mt-4 px-3.5 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[12.5px] font-semibold leading-relaxed">
                Status GAGAL akan mencegah sertifikat diterbitkan untuk intern ini.
            </div>
            <form method="POST" :action="detail ? '{{ url('/interns') }}/' + detail.id + '/mark-failed' : ''">
                @csrf
                <div class="mt-4">
                    <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Alasan</label>
                    <textarea name="reason" x-model="failedReason" required placeholder="Minimal 10 karakter..." class="w-full box-border p-3 border border-dash-border rounded-lg text-[13.5px] min-h-[90px] outline-none resize-y focus:border-red-400"></textarea>
                    <div class="text-[11.5px] text-dash-muted mt-1">Minimal 10 karakter.</div>
                </div>
                <div class="flex gap-2.5 mt-4.5 justify-end">
                    <button type="button" @click="showMarkFailed = false" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                    <button type="submit" :disabled="failedReason.length < 10" class="px-4.5 py-2.5 rounded-lg border-0 bg-red-600 text-white text-[13.5px] font-bold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">Tandai Gagal</button>
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

                    <template x-if="isAdminMagang && (detail.status === 'active' || detail.status === 'extended')">
                        <div class="mt-4 pt-4 border-t border-dash-border">
                            <div class="text-[11.5px] font-bold text-dash-faint uppercase tracking-wide mb-3">Aksi Status</div>
                            <div class="flex gap-2 flex-wrap">
                                <button type="button" @click="openExtend()" class="flex items-center gap-1.5 px-3.5 py-2 bg-violet-50 text-violet-700 border border-violet-200 rounded-lg text-[12.5px] font-bold cursor-pointer hover:bg-violet-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                    Perpanjang Masa Magang
                                </button>
                                <button type="button" @click="showMarkCompleted = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-[12.5px] font-bold cursor-pointer hover:bg-blue-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Tandai Selesai
                                </button>
                                <button type="button" @click="openMarkFailed()" class="flex items-center gap-1.5 px-3.5 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg text-[12.5px] font-bold cursor-pointer hover:bg-red-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Tandai Gagal
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function internPage(config) {
    return {
        showAdd: config.openAdd,
        showReject: false,
        rejectName: '',
        rejectAction: '',
        showDetail: false,
        detail: null,
        subTab: 'absensi',
        // Bulk delete — `rows` is the {id, name} list for whichever table is
        // actually rendered (list or pending tab; only one exists in the DOM
        // per page load, both tabs are separate server round trips). Reused
        // both for "select all" and for the modal's name list, so no fetch.
        rows: config.rows,
        selectedIds: [],
        showBulkDelete: false,
        bulkDeleteSubmitting: false,
        confirmText: '',
        init() {},
        toggleOne(id, checked) {
            this.selectedIds = checked
                ? [...this.selectedIds, id]
                : this.selectedIds.filter(x => x !== id);
        },
        toggleSelectAll(checked) {
            this.selectedIds = checked ? this.rows.map(r => r.id) : [];
        },
        allSelected() {
            return this.rows.length > 0 && this.selectedIds.length === this.rows.length;
        },
        selectedNames() {
            return this.rows.filter(r => this.selectedIds.includes(r.id)).map(r => r.name);
        },
        openBulkDelete() {
            this.confirmText = '';
            this.bulkDeleteSubmitting = false;
            this.showBulkDelete = true;
        },
        closeBulkDelete() {
            this.showBulkDelete = false;
        },
        // Reset password
        showResetPassword: false,
        resetInternId: null,
        resetInternName: '',
        resetMode: 'random',
        resetCustomPassword: '',
        resetSubmitting: false,
        resetError: '',
        resetResultPassword: null,
        resetCopied: false,
        openResetPassword(id, name) {
            this.resetInternId = id;
            this.resetInternName = name;
            this.resetMode = 'random';
            this.resetCustomPassword = '';
            this.resetSubmitting = false;
            this.resetError = '';
            this.resetResultPassword = null;
            this.resetCopied = false;
            this.showResetPassword = true;
        },
        closeResetPassword() {
            this.showResetPassword = false;
        },
        async submitResetPassword() {
            if (this.resetMode === 'custom' && this.resetCustomPassword.length < 8) {
                this.resetError = 'Password minimal 8 karakter.';
                return;
            }
            this.resetSubmitting = true;
            this.resetError = '';
            try {
                const res = await fetch(`${config.resetPasswordUrlBase}/${this.resetInternId}/reset-password`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: JSON.stringify({
                        mode: this.resetMode,
                        password: this.resetMode === 'custom' ? this.resetCustomPassword : null,
                    }),
                });
                const data = await res.json().catch(() => null);
                if (!res.ok || !data || !data.success) {
                    this.resetError = (data && data.message)
                        || (data && data.data && Object.values(data.data).flat().join(' '))
                        || 'Gagal mereset password.';
                    return;
                }
                this.resetResultPassword = data.data.password;
            } catch (e) {
                this.resetError = 'Terjadi kesalahan jaringan, silakan coba lagi.';
            } finally {
                this.resetSubmitting = false;
            }
        },
        async copyResetPassword() {
            try {
                await navigator.clipboard.writeText(this.resetResultPassword);
                this.resetCopied = true;
                setTimeout(() => { this.resetCopied = false; }, 2000);
            } catch (e) {
                this.resetError = 'Gagal menyalin ke clipboard — salin manual dari kotak di atas.';
            }
        },
        // Status actions
        isAdminMagang: config.isAdminMagang,
        showExtend: false,
        extendDate: '',
        extendMinDate: '',
        extendReason: '',
        showMarkCompleted: false,
        showMarkFailed: false,
        failedReason: '',
        openExtend() {
            if (!this.detail) return;
            const d = new Date(this.detail.endDate);
            d.setDate(d.getDate() + 1);
            this.extendMinDate = d.toISOString().slice(0, 10);
            this.extendDate = '';
            this.extendReason = '';
            this.showExtend = true;
        },
        openMarkFailed() {
            this.failedReason = '';
            this.showMarkFailed = true;
        },
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
