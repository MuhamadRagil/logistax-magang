@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="flex min-h-screen w-full bg-[#F5F7FA]">
    <div class="flex-1 basis-[46%] min-w-[320px] bg-[linear-gradient(150deg,#001F5C_0%,#003087_55%,#00577A_100%)] flex flex-col items-center justify-center px-10 py-16 relative overflow-hidden">
        <div class="absolute -top-[60px] -right-[60px] w-[220px] h-[220px] bg-[rgba(0,166,192,0.18)] rounded-full"></div>
        <div class="absolute -bottom-20 -left-10 w-[260px] h-[260px] bg-white/[0.06] rounded-full"></div>
        <div class="bg-white rounded-2xl px-8 py-6 shadow-[0_20px_40px_rgba(0,15,50,0.25)] z-10">
            <img src="{{ asset('images/dashboard-logo.png') }}" alt="Logistax" class="h-11 block">
        </div>
        <div class="mt-8 text-center z-10">
            <div class="text-white text-[28px] font-bold tracking-wide">Sistem Manajemen Magang</div>
            <div class="text-white/70 text-[15px] font-normal mt-2.5 max-w-[380px]">Kelola pendaftaran, absensi, penilaian, dan sertifikat intern dalam satu platform.</div>
        </div>
    </div>

    <div class="flex-1 basis-[54%] min-w-[320px] flex items-center justify-center p-10">
        <div class="w-full max-w-[380px]">
            <div class="text-2xl font-extrabold text-dash-ink">Masuk ke akun Anda</div>
            <div class="text-sm text-dash-muted mt-1.5">Gunakan kredensial admin atau supervisor mentor Anda.</div>

            @if ($errors->any())
                <div class="mt-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[13px] font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-8 flex flex-col gap-4.5">
                @csrf
                <div>
                    <label class="block text-[13px] font-semibold text-dash-ink mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@logistax.id" required autofocus
                        class="w-full box-border px-3.5 py-3 border border-dash-border rounded-lg text-sm text-dash-ink outline-none focus:border-dash-teal">
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-dash-ink mb-1.5">Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" name="password" placeholder="••••••••" required
                            class="w-full box-border px-3.5 py-3 pr-11 border border-dash-border rounded-lg text-sm text-dash-ink outline-none focus:border-dash-teal">
                        <button type="button" @click="show = !show" class="absolute right-2.5 top-1/2 -translate-y-1/2 bg-transparent border-0 cursor-pointer p-1 flex items-center justify-center">
                            <span class="w-5 h-5 rounded-full border-[1.5px] border-dash-faint relative block">
                                <span class="w-2 h-2 rounded-full absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" :class="show ? 'bg-dash-teal' : 'bg-dash-faint'"></span>
                            </span>
                        </button>
                    </div>
                </div>
                <div class="flex justify-end">
                    <a href="#" class="text-[13px] text-dash-teal font-semibold no-underline hover:underline">Lupa password?</a>
                </div>
                <button type="submit" class="w-full py-3.5 bg-dash-navy text-white border-0 rounded-lg text-[15px] font-bold cursor-pointer mt-2 hover:bg-dash-navy-dark">
                    Login
                </button>
            </form>

            <div class="mt-7 px-4 py-3.5 bg-[#F5F7FA] rounded-[10px] text-[12.5px] text-dash-muted leading-relaxed">
                Pendaftaran intern baru dilakukan melalui aplikasi mobile, bukan dashboard ini.
            </div>
        </div>
    </div>
</div>
@endsection
