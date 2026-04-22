<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk – Sistem Informasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex">

    {{-- ===================== LEFT PANEL ===================== --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-5/12 bg-[#0f2942] flex-col justify-between p-12 relative overflow-hidden">

        {{-- Background decoration --}}
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-[#1a3d5c] opacity-60"></div>
            <div class="absolute bottom-0 -left-16 w-72 h-72 rounded-full bg-[#163352] opacity-50"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] rounded-full bg-[#122e4a] opacity-40"></div>
        </div>

        {{-- Logo & Brand --}}
        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center shadow-lg shadow-sky-500/30">
                    <img src="{{ asset('images/logo_dkj.jpg') }}" alt="DKJ Logo"
                    class="w-10 h-10 rounded-xl object-contain bg-white p-1 shadow-lg">
                </div>
                <span class="text-white font-bold text-xl tracking-tight">PT. Dunia Kimia Jaya</span>
            </div>
        </div>

        {{-- Center content --}}
        <div class="relative z-10 space-y-6">
            <div class="w-14 h-1 bg-sky-500 rounded-full"></div>
            <h1 class="text-4xl font-bold text-white leading-tight">
                Halo, <span class="text-sky-400">Selamat</span><br>Datang Kembali!
            </h1>
            <p class="text-slate-400 text-base leading-relaxed max-w-sm">
                Masuk ke akun Anda untuk mengakses sistem pengelola data AR dan melanjutkan aktivitas Anda.
            </p>

        </div>

        {{-- Footer --}}
        <div class="relative z-10">
            <p class="text-slate-500 text-xs">© {{ date('Y') }} PT. Dunia Kimia Jaya</p>
        </div>
    </div>

    {{-- ===================== RIGHT PANEL (FORM) ===================== --}}
    <div class="flex-1 flex items-center justify-center px-6 py-12 lg:px-12">
        <div class="w-full max-w-md">

            {{-- Mobile logo --}}
            <div class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 bg-[#0f2942] rounded-lg flex items-center justify-center">
                    <img src="{{ asset('images/logo_dkj.jpg') }}" alt="DKJ Logo"
                    class="w-10 h-10 rounded-xl object-contain bg-white p-1 shadow-lg">
                </div>
                <span class="font-bold text-[#0f2942] text-lg">DKJ<span class="text-sky-500">Sistem</span></span>
            </div>

            {{-- Heading --}}
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-800">Masuk ke Akun</h2>
                <p class="text-slate-500 text-sm mt-1">Masukkan email dan kata sandi Anda</p>
            </div>

            {{-- Session status --}}
            @if (session('status'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-green-700">{{ session('status') }}</p>
                </div>
            </div>
            @endif

            {{-- Error messages --}}
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <ul class="text-sm text-red-600 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            {{-- Form --}}
            <form action="/login" method="POST" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="contoh@email.com"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-slate-800 placeholder-slate-400
                                   focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition-all
                                   @error('email') border-red-400 focus:ring-red-400/30 focus:border-red-400 @enderror"
                        >
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-slate-700">
                            Kata Sandi
                        </label>
                        @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs text-sky-600 hover:text-sky-700 hover:underline font-medium transition-colors">
                            Lupa kata sandi?
                        </a>
                        @endif
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan kata sandi"
                            required
                            autocomplete="current-password"
                            class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-slate-800 placeholder-slate-400
                                   focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition-all
                                   @error('password') border-red-400 focus:ring-red-400/30 focus:border-red-400 @enderror"
                        >
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full py-3 px-4 bg-[#0f2942] hover:bg-[#163352] text-white font-semibold text-sm rounded-xl
                           transition-all duration-200 shadow-lg shadow-slate-900/20 hover:shadow-xl hover:shadow-slate-900/25
                           focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 mt-1"
                >
                    Masuk ke Sistem
                </button>

            </form>


        </div>
    </div>

</body>
</html>