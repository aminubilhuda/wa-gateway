@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-3 sm:space-y-lg">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-1 sm:space-x-xs text-on-surface-variant font-label-md text-label-md mb-2 sm:mb-lg text-xs sm:text-sm">
            <a class="hover:text-primary" href="{{ route('dashboard') }}">Dashboard</a>
            <span class="material-symbols-outlined text-[12px] sm:text-[14px]">chevron_right</span>
            <span class="text-primary font-bold">Profile</span>
        </nav>

        <!-- Page Header -->
        <div class="mb-3 sm:mb-xl">
            <h2 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-surface mb-1 sm:mb-xs">Edit Profile</h2>
            <p class="text-sm sm:text-body-md text-body-md text-on-surface-variant">
                Perbarui informasi profil dan kata sandi akun Anda.
            </p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-lg py-2 sm:py-md rounded-xl mb-3 sm:mb-lg text-xs sm:text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-lg py-2 sm:py-md rounded-xl mb-3 sm:mb-lg text-xs sm:text-sm">
                <ul class="list-disc pl-3 sm:pl-md space-y-0.5 sm:space-y-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Profile Form Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden">
            <div class="px-3 sm:px-lg py-2 sm:py-md bg-surface-container-low border-b border-outline-variant">
                <h4 class="font-headline-md text-headline-md text-sm sm:text-base text-on-surface">Informasi Profil</h4>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" class="p-3 sm:p-lg space-y-3 sm:space-y-lg">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[10px] sm:text-label-md font-bold text-on-surface mb-1 sm:mb-xs">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full bg-surface-container-low border border-outline-variant p-1.5 sm:p-sm rounded-lg text-xs sm:text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                </div>

                <div>
                    <label class="block text-[10px] sm:text-label-md font-bold text-on-surface mb-1 sm:mb-xs">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full bg-surface-container-low border border-outline-variant p-1.5 sm:p-sm rounded-lg text-xs sm:text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                </div>

                <div class="border-t border-outline-variant pt-3 sm:pt-lg">
                    <h4 class="font-headline-md text-headline-md text-sm sm:text-base text-on-surface mb-2 sm:mb-md">Ubah Kata Sandi</h4>
                    <p class="text-[10px] sm:text-label-md text-on-surface-variant mb-2 sm:mb-md">Kosongkan jika tidak ingin mengubah.</p>

                    <div class="mb-2 sm:mb-md">
                        <label class="block text-[10px] sm:text-label-md font-bold text-on-surface mb-1 sm:mb-xs">Kata Sandi Saat Ini</label>
                        <input type="password" name="current_password"
                               class="w-full bg-surface-container-low border border-outline-variant p-1.5 sm:p-sm rounded-lg text-xs sm:text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>

                    <div class="mb-2 sm:mb-md">
                        <label class="block text-[10px] sm:text-label-md font-bold text-on-surface mb-1 sm:mb-xs">Kata Sandi Baru</label>
                        <input type="password" name="new_password"
                               class="w-full bg-surface-container-low border border-outline-variant p-1.5 sm:p-sm rounded-lg text-xs sm:text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>

                    <div>
                        <label class="block text-[10px] sm:text-label-md font-bold text-on-surface mb-1 sm:mb-xs">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="new_password_confirmation"
                               class="w-full bg-surface-container-low border border-outline-variant p-1.5 sm:p-sm rounded-lg text-xs sm:text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                </div>

                <div class="pt-2 sm:pt-md flex justify-end gap-2 sm:gap-sm border-t border-outline-variant/30">
                    <a href="{{ route('dashboard') }}" class="px-2 sm:px-md py-1.5 sm:py-sm border border-outline text-on-surface rounded-lg font-bold hover:bg-surface-container transition-colors text-xs sm:text-sm">Batal</a>
                    <button type="submit" class="px-2 sm:px-md py-1.5 sm:py-sm bg-primary text-on-primary rounded-lg font-bold hover:brightness-105 transition-all text-xs sm:text-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>

        <!-- Account Info Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-3 sm:p-lg space-y-2 sm:space-y-md">
            <h4 class="font-bold text-sm sm:text-body-lg text-on-surface">Informasi Akun</h4>
            <div class="grid grid-cols-2 gap-2 sm:gap-md">
                <div>
                    <p class="text-[9px] sm:text-label-sm text-on-surface-variant uppercase tracking-wider">Terdaftar Sejak</p>
                    <p class="font-bold text-xs sm:text-body-md text-on-surface">{{ $user->created_at->format('d M Y, H:i') }}</p>
                </div>
                <div>
                    <p class="text-[9px] sm:text-label-sm text-on-surface-variant uppercase tracking-wider">Terakhir Diperbarui</p>
                    <p class="font-bold text-xs sm:text-body-md text-on-surface">{{ $user->updated_at->format('d M Y, H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
