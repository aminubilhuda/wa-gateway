<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Lupa Password | WA-Blast Pro</title>
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
<body class="min-h-screen flex items-center justify-center p-6 text-slate-800">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-[#25d366]/10 text-[#006d2f] rounded-2xl mb-4 shadow-sm border border-[#25d366]/20">
                <span class="material-symbols-outlined text-[36px]" style="font-variation-settings: 'FILL' 1;">lock_reset</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-[#0b1c30]">WA-Blast <span class="text-[#006d2f]">Pro</span></h1>
            <p class="text-sm text-slate-500 mt-1">Reset password akun Anda</p>
        </div>

        <div class="glass-card rounded-2xl p-8 transition-all duration-300 hover:shadow-lg">
            <h2 class="text-xl font-bold text-[#0b1c30] mb-2">Lupa Password?</h2>
            <p class="text-sm text-slate-500 mb-6">Masukkan email Anda dan kami akan mengirimkan tautan untuk mereset password.</p>

            @if(session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl mb-6 text-sm flex items-start gap-3">
                    <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5">check_circle</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6 text-sm flex items-start gap-3">
                    <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5">error</span>
                    <div>
                        <span class="font-bold">Gagal!</span>
                        <ul class="list-disc pl-4 mt-1 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Alamat Email</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full pl-10 pr-4 py-3 bg-white/70 border border-slate-200 rounded-xl focus:border-[#006d2f] focus:ring focus:ring-[#25d366]/20 outline-none transition-all text-sm"
                            placeholder="nama@email.com">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-[#006d2f] hover:bg-[#005223] text-white font-bold py-3.5 px-4 rounded-xl shadow-md hover:shadow-lg active:scale-[0.98] transition-all flex items-center justify-center gap-2 text-sm">
                    <span>Kirim Tautan Reset</span>
                    <span class="material-symbols-outlined text-[18px]">send</span>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center text-sm text-slate-500">
                <a href="{{ route('login') }}" class="text-[#006d2f] hover:underline font-semibold flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Kembali ke halaman login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
