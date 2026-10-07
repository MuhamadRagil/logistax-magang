@extends('layouts.app')

@section('title', 'Profil')

@section('content')
<div class="max-w-[640px]">
    <div class="bg-white border border-dash-border rounded-xl p-6.5 mb-5">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 rounded-full bg-dash-navy text-white text-xl font-bold flex items-center justify-center flex-shrink-0">
                {{ $admin->initials() }}
            </div>
            <div>
                <div class="text-lg font-extrabold text-dash-ink">{{ $admin->name }}</div>
                <div class="text-[13px] text-dash-muted break-all">{{ $admin->email }}</div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 p-4 bg-dash-bg rounded-lg mb-6">
            <div>
                <div class="text-[11.5px] font-bold text-dash-faint uppercase">Role</div>
                <div class="text-[13.5px] text-dash-ink mt-1 font-semibold">{{ $admin->roleLabel() }}</div>
            </div>
            <div>
                <div class="text-[11.5px] font-bold text-dash-faint uppercase">Divisi</div>
                <div class="text-[13.5px] text-dash-ink mt-1 font-semibold">{{ $admin->division?->name ?? '—' }}</div>
            </div>
            <div class="col-span-2">
                <div class="text-[11.5px] font-bold text-dash-faint uppercase">Email</div>
                <div class="text-[13.5px] text-dash-ink mt-1 font-semibold break-all">{{ $admin->email }}</div>
            </div>
        </div>

        <div class="text-[14.5px] font-bold text-dash-ink mb-3.5">Ubah Nama</div>
        <form method="POST" action="{{ route('profile.update-name') }}">
            @csrf
            @method('PATCH')
            <div class="flex gap-3">
                <input name="name" value="{{ old('name', $admin->name) }}" required class="flex-1 px-3.5 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                <button type="submit" class="px-5 py-2.5 bg-dash-navy text-white border-0 rounded-lg text-[13.5px] font-bold cursor-pointer hover:bg-dash-navy-dark whitespace-nowrap">Simpan</button>
            </div>
            @error('name')
                <div class="text-[12.5px] text-red-600 mt-1.5">{{ $message }}</div>
            @enderror
        </form>
    </div>

    <div class="bg-white border border-dash-border rounded-xl p-6.5">
        <div class="text-[14.5px] font-bold text-dash-ink mb-4.5">Ubah Password</div>
        <form method="POST" action="{{ route('profile.update-password') }}" class="flex flex-col gap-3.5">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Password Saat Ini</label>
                <input type="password" name="current_password" required autocomplete="current-password" class="w-full box-border px-3.5 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                @error('current_password')
                    <div class="text-[12.5px] text-red-600 mt-1.5">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Password Baru</label>
                <input type="password" name="password" required autocomplete="new-password" class="w-full box-border px-3.5 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                <div class="text-[11.5px] text-dash-muted mt-1">Minimal 8 karakter.</div>
                @error('password')
                    <div class="text-[12.5px] text-red-600 mt-1.5">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full box-border px-3.5 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
            </div>
            <div class="mt-1">
                <button type="submit" class="px-5 py-2.5 bg-dash-navy text-white border-0 rounded-lg text-[13.5px] font-bold cursor-pointer hover:bg-dash-navy-dark">Ubah Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
