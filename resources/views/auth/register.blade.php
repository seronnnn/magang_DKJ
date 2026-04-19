<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun – Sistem Informasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-100 flex">

    {{-- ── Left panel ── --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-5/12 bg-[#0f2942] flex-col justify-between p-12 relative overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-[#1a3d5c] opacity-60"></div>
            <div class="absolute bottom-0 -left-16 w-72 h-72 rounded-full bg-[#163352] opacity-50"></div>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center shadow-lg shadow-sky-500/30">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <span class="text-white font-bold text-xl tracking-tight">PT. Dunia Kimia Jaya</span>
            </div>
        </div>
        <div class="relative z-10 space-y-6">
            <div class="w-14 h-1 bg-sky-500 rounded-full"></div>
            <h1 class="text-4xl font-bold text-white leading-tight">Buat Akun <span class="text-sky-400">Baru</span></h1>
            <p class="text-slate-400 text-base leading-relaxed max-w-sm">Daftarkan akun Anda untuk mengakses sistem AR Dashboard DKJ.</p>
        </div>
        <div class="relative z-10">
            <p class="text-slate-500 text-xs">© {{ date('Y') }} PT. Dunia Kimia Jaya</p>
        </div>
    </div>

    {{-- ── Right panel ── --}}
    <div class="flex-1 flex items-center justify-center px-6 py-12 lg:px-12">
        <div class="w-full max-w-md">

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-800">Buat Akun Baru</h2>
                <p class="text-slate-500 text-sm mt-1">Lengkapi form berikut untuk mendaftar</p>
            </div>

            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <ul class="text-sm text-red-600 space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form action="/register" method="POST" class="space-y-5">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap" required autocomplete="name"
                        class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition-all @error('name') border-red-400 @enderror">
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="contoh@dkj.co.id" required autocomplete="email"
                        class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition-all @error('email') border-red-400 @enderror">
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata Sandi</label>
                    <input type="password" id="password" name="password" placeholder="Minimal 5 karakter" required autocomplete="new-password"
                        class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition-all @error('password') border-red-400 @enderror">
                </div>

                {{-- Role — now 3 options --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Peran Akun</label>
                    <div class="grid grid-cols-3 gap-3">

                        {{-- Collector --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="role" value="collector" class="peer sr-only"
                                   {{ old('role', 'collector') === 'collector' ? 'checked' : '' }}>
                            <div class="flex flex-col items-center gap-2 p-3 border-2 border-slate-200 rounded-xl bg-white text-center transition-all peer-checked:border-sky-500 peer-checked:bg-sky-50 hover:border-slate-300">
                                <span class="text-xl">👤</span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-700">Collector</p>
                                    <p class="text-xs text-slate-400">Penagih</p>
                                </div>
                                <span class="absolute top-2 right-2 w-4 h-4 rounded-full bg-sky-500 hidden peer-checked:flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                            </div>
                        </label>

                        {{-- Manager --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="role" value="manager" class="peer sr-only"
                                   {{ old('role') === 'manager' ? 'checked' : '' }}>
                            <div class="flex flex-col items-center gap-2 p-3 border-2 border-slate-200 rounded-xl bg-white text-center transition-all peer-checked:border-sky-500 peer-checked:bg-sky-50 hover:border-slate-300">
                                <span class="text-xl">📋</span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-700">Manager</p>
                                    <p class="text-xs text-slate-400">Manajer</p>
                                </div>
                                <span class="absolute top-2 right-2 w-4 h-4 rounded-full bg-sky-500 hidden peer-checked:flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                            </div>
                        </label>

                        {{-- Admin --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="role" value="admin" class="peer sr-only"
                                   {{ old('role') === 'admin' ? 'checked' : '' }}>
                            <div class="flex flex-col items-center gap-2 p-3 border-2 border-slate-200 rounded-xl bg-white text-center transition-all peer-checked:border-sky-500 peer-checked:bg-sky-50 hover:border-slate-300">
                                <span class="text-xl">🛡️</span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-700">Admin</p>
                                    <p class="text-xs text-slate-400">Administrator</p>
                                </div>
                                <span class="absolute top-2 right-2 w-4 h-4 rounded-full bg-sky-500 hidden peer-checked:flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                            </div>
                        </label>

                    </div>
                    @error('role')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <button type="submit"
                    class="w-full py-3 px-4 bg-[#0f2942] hover:bg-[#163352] text-white font-semibold text-sm rounded-xl transition-all duration-200 shadow-lg shadow-slate-900/20 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    Daftar Sekarang
                </button>
            </form>

            <p class="text-center text-sm text-slate-500 mt-6">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-semibold text-sky-600 hover:text-sky-700 hover:underline transition-colors">Masuk di sini</a>
            </p>
        </div>
    </div>
</body>
</html>