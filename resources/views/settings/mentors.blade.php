@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
@include('settings.partials.tabs')

<div x-data="{
        showModal: @js($errors->any()),
        mode: @js(old('_mode', 'create')),
        editId: null,
        form: { name: @js(old('name', '')), email: @js(old('email', '')), division_id: @js(old('division_id', '')) },
        mentors: @js($mentors->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'email' => $m->email, 'division_id' => $m->division_id])->values()),

        get formAction() {
            return this.mode === 'edit'
                ? '{{ url('/settings/mentors') }}/' + this.editId
                : '{{ route('settings.mentors.store') }}';
        },

        openCreate() {
            this.mode = 'create';
            this.editId = null;
            this.form = { name: '', email: '', division_id: '' };
            this.showModal = true;
        },

        openEdit(id) {
            const m = this.mentors.find(x => x.id === id);
            if (!m) return;
            this.mode = 'edit';
            this.editId = id;
            this.form = { name: m.name, email: m.email, division_id: m.division_id || '' };
            this.showModal = true;
        },
    }">

    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <div class="text-base font-extrabold text-dash-ink">Mentor</div>
            <div class="text-[13px] text-dash-muted mt-0.5">Kelola supervisor mentor yang membimbing intern selama magang.</div>
        </div>
        <button type="button" @click="openCreate()" class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-lg border-0 bg-dash-navy text-white text-[13.5px] font-bold cursor-pointer hover:bg-dash-navy-dark">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Mentor
        </button>
    </div>

    <div class="bg-white border border-dash-border rounded-xl overflow-hidden">
        <div class="grid grid-cols-[2fr_1.5fr_1.2fr_1fr_1.2fr] px-5 py-3.5 bg-dash-thead border-b border-dash-border text-[11.5px] font-bold text-dash-faint uppercase tracking-wide">
            <div>Nama</div><div>Email</div><div>Divisi</div><div>Intern Aktif</div><div>Aksi</div>
        </div>
        @forelse ($mentors as $mentor)
            <div class="grid grid-cols-[2fr_1.5fr_1.2fr_1fr_1.2fr] px-5 py-3.5 border-b border-dash-border-soft items-center {{ $mentor->is_active ? '' : 'bg-dash-thead' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full text-white text-xs font-bold flex items-center justify-center flex-shrink-0" style="background:{{ \App\Support\Badge::avatarColor($mentor->name) }}">{{ $mentor->initials() }}</div>
                    <span class="text-[13.5px] font-bold {{ $mentor->is_active ? 'text-dash-ink' : 'text-dash-muted' }}">{{ $mentor->name }}</span>
                </div>
                <div class="text-[13px] text-dash-slate">{{ $mentor->email }}</div>
                <div class="text-[13px] text-dash-slate">{{ $mentor->division?->name ?? '—' }}</div>
                <div class="text-[13px] text-dash-slate">{{ $mentor->mentored_interns_count }}</div>
                <div class="flex gap-2 flex-wrap">
                    @if ($mentor->is_active)
                        <button type="button" @click="openEdit('{{ $mentor->id }}')" class="border border-dash-border bg-white rounded-md px-3 py-1.5 text-xs font-bold text-dash-navy cursor-pointer">Edit</button>
                        <form method="POST" action="{{ route('settings.mentors.destroy', $mentor) }}" onsubmit="return confirm('Hapus mentor &quot;{{ $mentor->name }}&quot;?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="border border-red-200 bg-red-50 rounded-md px-3 py-1.5 text-xs font-bold text-red-700 cursor-pointer">Hapus</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            @include('partials.empty-state', [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
                'title' => 'Belum ada mentor',
                'description' => 'Klik "Tambah Mentor" untuk menambahkan mentor pertama.',
            ])
        @endforelse
    </div>

    {{-- Tambah / Edit modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[480px] max-w-[94vw] p-6.5" @click.outside="showModal = false">
            <div class="text-base font-extrabold text-dash-ink mb-4.5" x-text="mode === 'edit' ? 'Edit Mentor' : 'Tambah Mentor'"></div>
            <form method="POST" :action="formAction">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="mode !== 'edit'">
                <input type="hidden" name="_mode" :value="mode">

                <div class="flex flex-col gap-3.5">
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Nama Lengkap</label>
                        <input name="name" x-model="form.name" required placeholder="mis. Dr. Budi Santoso" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                    </div>
                    <div x-show="mode === 'create'">
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Email</label>
                        <input type="email" name="email" x-model="form.email" :required="mode === 'create'" placeholder="mentor@logistax.test" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                        <div class="text-[11.5px] text-dash-muted mt-1">Password akan digenerate otomatis dan ditampilkan di flash message.</div>
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Divisi</label>
                        <select name="division_id" x-model="form.division_id" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                            <option value="">— Pilih Divisi —</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
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
                    <button type="button" @click="showModal = false" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                    <button type="submit" class="px-4.5 py-2.5 rounded-lg border-0 bg-dash-navy text-white text-[13.5px] font-bold cursor-pointer hover:bg-dash-navy-dark">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
