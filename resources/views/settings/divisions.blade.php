@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
@include('settings.partials.tabs')

<div x-data="{
        showModal: @js($errors->any()),
        mode: @js(old('_mode', 'create')),
        editId: null,
        form: { name: @js(old('name', '')) },
        divisions: @js($divisions->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'is_active' => $d->is_active])->values()),

        get formAction() {
            return this.mode === 'edit'
                ? '{{ url('/settings/divisions') }}/' + this.editId
                : '{{ route('settings.divisions.store') }}';
        },

        openCreate() {
            this.mode = 'create';
            this.editId = null;
            this.form = { name: '' };
            this.showModal = true;
        },

        openEdit(id) {
            const d = this.divisions.find(x => x.id === id);
            if (!d) return;
            this.mode = 'edit';
            this.editId = id;
            this.form = { name: d.name };
            this.showModal = true;
        },
    }">

    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <div class="text-base font-extrabold text-dash-ink">Divisi</div>
            <div class="text-[13px] text-dash-muted mt-0.5">Kelola divisi yang tersedia untuk penempatan intern dan mentor.</div>
        </div>
        <button type="button" @click="openCreate()" class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-lg border-0 bg-dash-navy text-white text-[13.5px] font-bold cursor-pointer hover:bg-dash-navy-dark">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Divisi
        </button>
    </div>

    <div class="bg-white border border-dash-border rounded-xl overflow-hidden max-lg:overflow-x-auto">
        <div class="grid max-lg:min-w-[680px] grid-cols-[2.5fr_1fr_1fr_1fr_1.2fr] px-5 py-3.5 bg-dash-thead border-b border-dash-border text-[11.5px] font-bold text-dash-faint uppercase tracking-wide">
            <div>Nama Divisi</div><div>Intern Aktif</div><div>Mentor</div><div>Status</div><div>Aksi</div>
        </div>
        @forelse ($divisions as $division)
            <div class="grid max-lg:min-w-[680px] grid-cols-[2.5fr_1fr_1fr_1fr_1.2fr] px-5 py-3.5 border-b border-dash-border-soft items-center {{ $division->is_active ? '' : 'bg-dash-thead' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 {{ $division->is_active ? 'bg-dash-pill text-dash-navy' : 'bg-dash-bg text-dash-faint' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <span class="text-[13.5px] font-bold {{ $division->is_active ? 'text-dash-ink' : 'text-dash-muted' }}">{{ $division->name }}</span>
                </div>
                <div class="text-[13px] text-dash-slate">{{ $division->interns_count }}</div>
                <div class="text-[13px] text-dash-slate">{{ $division->mentors_count }}</div>
                <div>
                    @if ($division->is_active)
                        <span class="text-[11.5px] font-bold px-2.5 py-1 rounded-full text-green-700 bg-green-100">Aktif</span>
                    @else
                        <span class="text-[11.5px] font-bold px-2.5 py-1 rounded-full text-dash-muted bg-dash-bg">Nonaktif</span>
                    @endif
                </div>
                <div class="flex gap-2 flex-wrap">
                    @if ($division->is_active)
                        <button type="button" @click="openEdit('{{ $division->id }}')" class="border border-dash-border bg-white rounded-md px-3 py-1.5 text-xs font-bold text-dash-navy cursor-pointer">Edit</button>
                        <form method="POST" action="{{ route('settings.divisions.destroy', $division) }}" onsubmit="return confirm('Hapus divisi &quot;{{ $division->name }}&quot;?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="border border-red-200 bg-red-50 rounded-md px-3 py-1.5 text-xs font-bold text-red-700 cursor-pointer">Hapus</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            @include('partials.empty-state', [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />',
                'title' => 'Belum ada divisi',
                'description' => 'Buat divisi pertama agar intern dan mentor bisa ditempatkan.',
                'actionLabel' => '+ Tambah Divisi',
                'actionClick' => 'openCreate()',
            ])
        @endforelse
    </div>

    {{-- Tambah / Edit modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[440px] max-w-[94vw] p-6.5" @click.outside="showModal = false">
            <div class="text-base font-extrabold text-dash-ink mb-4.5" x-text="mode === 'edit' ? 'Edit Divisi' : 'Tambah Divisi'"></div>
            <form method="POST" :action="formAction">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="mode !== 'edit'">
                <input type="hidden" name="_mode" :value="mode">

                <div>
                    <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Nama Divisi</label>
                    <input name="name" x-model="form.name" required placeholder="mis. IT Development" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
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
