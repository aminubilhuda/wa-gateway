<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login | WA-Blast Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f7f4 0%, #e6f4ea 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(0, 109, 47, 0.05);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 text-slate-800">
    <div class="w-full max-w-md">
        
        <!-- Logo / Brand -->
        <div class="text-center mb-6 sm:mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 bg-[#25d366]/10 text-[#006d2f] rounded-2xl mb-3 sm:mb-4 shadow-sm border border-[#25d366]/20">
                <span class="material-symbols-outlined text-[28px] sm:text-[36px]" style="font-variation-settings: 'FILL' 1;">bolt</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#0b1c30]">WA-Blast <span class="text-[#006d2f]">Pro</span></h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Enterprise WhatsApp Gateway & Marketing SaaS</p>
        </div>

        <!-- Card -->
        <div class="glass-card rounded-2xl p-5 sm:p-8 transition-all duration-300 hover:shadow-lg">
            <h2 class="text-lg sm:text-xl font-bold text-[#0b1c30] mb-4 sm:mb-6">Masuk ke Akun Anda</h2>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-3 sm:p-4 rounded-xl mb-4 sm:mb-6 text-xs sm:text-sm flex items-start gap-2 sm:gap-3">
                    <span class="material-symbols-outlined text-[18px] sm:text-[20px] shrink-0 mt-0.5">error</span>
                    <div>
                        <span class="font-bold">Gagal masuk!</span>
                        <ul class="list-disc pl-3 sm:pl-4 mt-1 space-y-0.5 sm:space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-4 sm:space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-[10px] sm:text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5 sm:mb-2">Alamat Email</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] sm:text-[20px]">mail</span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full pl-9 sm:pl-10 pr-3 sm:pr-4 py-2.5 sm:py-3 bg-white/70 border border-slate-200 rounded-xl focus:border-[#006d2f] focus:ring focus:ring-[#25d366]/20 outline-none transition-all text-xs sm:text-sm"
                            placeholder="nama@email.com">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex justify-between items-center mb-1.5 sm:mb-2">
                        <label for="password" class="block text-[10px] sm:text-xs font-semibold text-slate-600 uppercase tracking-wider">Password</label>
                        <a href="{{ route('password.request') }}" class="text-[10px] sm:text-xs text-[#006d2f] hover:underline font-semibold">Lupa password?</a>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] sm:text-[20px]">lock</span>
                        <input type="password" id="password" name="password" required
                            class="w-full pl-9 sm:pl-10 pr-3 sm:pr-4 py-2.5 sm:py-3 bg-white/70 border border-slate-200 rounded-xl focus:border-[#006d2f] focus:ring focus:ring-[#25d366]/20 outline-none transition-all text-xs sm:text-sm"
                            placeholder="Masukkan password Anda">
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs sm:text-sm">
                    <label class="flex items-center gap-1.5 sm:gap-2 cursor-pointer text-slate-600">
                        <input type="checkbox" name="remember" class="rounded text-[#006d2f] focus:ring-[#25d366]/20 border-slate-300 w-3.5 h-3.5 sm:w-4 sm:h-4">
                        <span>Ingat saya</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-[#006d2f] hover:bg-[#005223] text-white font-bold py-3 sm:py-3.5 px-3 sm:px-4 rounded-xl shadow-md hover:shadow-lg active:scale-[0.98] transition-all flex items-center justify-center gap-1.5 sm:gap-2 text-xs sm:text-sm">
                    <span>Masuk</span>
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">arrow_forward</span>
                </button>
            </form>

            <div class="mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-slate-100 text-center text-xs sm:text-sm text-slate-500">
                Belum memiliki akun? 
                <a href="{{ route('register') }}" class="text-[#006d2f] hover:underline font-semibold">Daftar sekarang</a>
            </div>
        </div>
    </div>
</body>
</html>
