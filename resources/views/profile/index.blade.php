@extends('layouts.app')

@section('title', 'Profil Saya')
@section('header', 'Profil Saya')
@section('subtitle', 'Kelola informasi akun kamu')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Profile Info Card --}}
    <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800">Informasi Profil</h2>
            <p class="text-xs text-gray-500 mt-0.5">Perbarui nama dan foto profil kamu</p>
        </header>
        <div class="p-5">
            {{-- Photo section --}}
            <div class="flex items-center gap-5 mb-6">
                <div class="relative">
                    @if(Auth::user()->url_profile)
                    <img src="{{ Auth::user()->url_profile }}" alt="Foto Profil"
                        class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 shadow-sm">
                    @else
                    <div class="w-20 h-20 rounded-full bg-primary-100 flex items-center justify-center border-2 border-gray-200 shadow-sm">
                        <span class="text-2xl font-bold text-primary-700">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    </div>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                    @if(Auth::user()->division)
                    <p class="text-xs text-gray-400 mt-0.5">{{ Auth::user()->division?->name }}</p>
                    @endif
                </div>
            </div>

            {{-- Photo upload form --}}
            <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data"
                class="mb-6 p-4 bg-gray-50 border border-gray-100 rounded-xl">
                @csrf @method('POST')
                <label class="block mb-2 text-xs font-medium text-gray-600 uppercase tracking-wide">Ganti Foto Profil</label>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300 flex-1 min-w-0">
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700 shrink-0">
                        Upload
                    </button>
                </div>
                <p class="mt-1.5 text-xs text-gray-400">Format: JPG, PNG. Maks 2MB.</p>
                @error('photo')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </form>

            {{-- Name/email update form --}}
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf @method('PUT')
                <x-alert-errors />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}" required
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                        @error('name')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}" required
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                        @error('email')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                </div>
                @if(isset($editable) && $editable)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">No. Telepon</label>
                        <input type="text" name="phone" value="{{ old('phone', Auth::user()->phone) }}"
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                        @error('phone')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                </div>
                @endif
                <div class="pt-1">
                    <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </article>

    {{-- Password Change Card --}}
    <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800">Ubah Password</h2>
            <p class="text-xs text-gray-500 mt-0.5">Pastikan akun kamu menggunakan password yang kuat dan unik</p>
        </header>
        <form method="POST" action="{{ route('profile.password') }}" class="p-5 space-y-4">
            @csrf @method('PUT')
            @if($errors->has('current_password') || $errors->has('password'))
            <div class="px-4 py-3 border rounded-lg bg-rose-50 border-rose-200 text-rose-700">
                <ul class="pl-5 space-y-1 list-disc text-sm">
                    @error('current_password')<li>{{ $message }}</li>@enderror
                    @error('password')<li>{{ $message }}</li>@enderror
                </ul>
            </div>
            @endif
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Password Saat Ini <span class="text-rose-500">*</span></label>
                <input type="password" name="current_password" required
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                @error('current_password')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Password Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    @error('password')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Konfirmasi Password Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="password_confirmation" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                </div>
            </div>
            <p class="text-xs text-gray-400">Minimal 8 karakter.</p>
            <div class="pt-1">
                <button type="submit"
                    class="px-5 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">
                    Ubah Password
                </button>
            </div>
        </form>
    </article>

    {{-- Read-only info card --}}
    <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800">Informasi Kepegawaian</h2>
        </header>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Divisi</p>
                <p class="font-medium text-gray-800">{{ Auth::user()->division?->name ?? '–' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Jabatan / Role</p>
                <p class="font-medium text-gray-800">{{ Auth::user()->getRoleNames()->implode(', ') ?? '–' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Bergabung Sejak</p>
                <p class="font-medium text-gray-800">{{ Auth::user()->created_at?->format('d F Y') ?? '–' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Status Akun</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                    <span class="w-1.5 h-1.5 mr-1 bg-emerald-500 rounded-full"></span>Aktif
                </span>
            </div>
        </div>
    </article>

</div>
@endsection
