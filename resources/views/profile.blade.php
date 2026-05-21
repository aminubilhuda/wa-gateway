@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-lg">
        <!-- Breadcrumbs -->
        <nav class="flex items-center space-x-xs text-on-surface-variant font-label-md text-label-md mb-lg">
            <a class="hover:text-primary" href="{{ route('dashboard') }}">Dashboard</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <span class="text-primary font-bold">Profile</span>
        </nav>

        <!-- Page Header -->
        <div class="mb-xl">
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-xs">Edit Profile</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">
                Perbarui informasi profil dan kata sandi akun Anda.
            </p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-lg py-md rounded-xl mb-lg">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-lg py-md rounded-xl mb-lg">
                <ul class="list-disc pl-md space-y-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Profile Form Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden">
            <div class="px-lg py-md bg-surface-container-low border-b border-outline-variant">
                <h4 class="font-headline-md text-headline-md text-on-surface">Informasi Profil</h4>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" class="p-lg space-y-lg">
                @csrf
                @method('PUT')

                <!-- Name -->
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                </div>

                <!-- Password Section Divider -->
                <div class="border-t border-outline-variant pt-lg">
                    <h4 class="font-headline-md text-headline-md text-on-surface mb-md">Ubah Kata Sandi</h4>
                    <p class="text-label-md text-on-surface-variant mb-md">Kosongkan bidang di bawah jika Anda tidak ingin mengubah kata sandi.</p>

                    <!-- Current Password -->
                    <div class="mb-md">
                        <label class="block text-label-md font-bold text-on-surface mb-xs">Kata Sandi Saat Ini</label>
                        <input type="password" name="current_password"
                               class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>

                    <!-- New Password -->
                    <div class="mb-md">
                        <label class="block text-label-md font-bold text-on-surface mb-xs">Kata Sandi Baru</label>
                        <input type="password" name="new_password"
                               class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>

                    <!-- Confirm New Password -->
                    <div>
                        <label class="block text-label-md font-bold text-on-surface mb-xs">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="new_password_confirmation"
                               class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-md flex justify-end gap-sm border-t border-outline-variant/30">
                    <a href="{{ route('dashboard') }}" class="px-md py-sm border border-outline text-on-surface rounded-lg font-bold hover:bg-surface-container transition-colors">Batal</a>
                    <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-bold hover:brightness-105 transition-all">Simpan Perubahan</button>
                </div>
            </form>
        </div>

        <!-- Account Info Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg space-y-md">
            <h4 class="font-bold text-body-lg text-on-surface">Informasi Akun</h4>
            <div class="grid grid-cols-2 gap-md">
                <div>
                    <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Terdaftar Sejak</p>
                    <p class="font-bold text-body-md text-on-surface">{{ $user->created_at->format('d M Y, H:i') }}</p>
                </div>
                <div>
                    <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Terakhir Diperbarui</p>
                    <p class="font-bold text-body-md text-on-surface">{{ $user->updated_at->format('d M Y, H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
