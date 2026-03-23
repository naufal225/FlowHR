@extends('errors.layout')

@section('title', 'Akses Ditolak')

@section('content')
    <div class="mb-4 text-5xl text-yellow-500">403</div>
    <h1 class="mb-2 text-2xl font-bold text-gray-900">Akses Ditolak</h1>
    <p class="mb-6 text-gray-600">
        Anda tidak memiliki izin untuk mengakses halaman ini.
    </p>
    @if(isset($exception) && $exception->getMessage())
        <div class="px-4 py-3 mb-6 text-sm text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg">
            {{ $exception->getMessage() }}
        </div>
    @elseif(!empty($message))
        <div class="px-4 py-3 mb-6 text-sm text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg">
            {{ $message }}
        </div>
    @endif
    <div class="space-y-3">
        @php
            $shouldLogin = !auth()->check();
            if (isset($exception) && $exception->getMessage()) {
                $msg = \Illuminate\Support\Str::lower($exception->getMessage());
                if (\Illuminate\Support\Str::contains($msg, ['belum punya divisi', 'login', 'expired', 'kadaluarsa'])) {
                    $shouldLogin = true;
                }
            }
        @endphp
        @if($shouldLogin && auth()->check())
            <form action="{{ route('logout') }}" method="POST" class="inline-block">
                @csrf
                <button type="submit"
                        class="px-6 py-2 text-white rounded-lg transition-colors bg-blue-600 hover:bg-blue-700">
                    Ke Login
                </button>
            </form>
        @elseif($shouldLogin)
            <a href="{{ route('login') }}"
               class="inline-block px-6 py-2 text-white rounded-lg transition-colors bg-blue-600 hover:bg-blue-700">
                Ke Login
            </a>
        @else
            <a href="{{ url('/') }}"
               class="inline-block px-6 py-2 text-white rounded-lg transition-colors bg-green-600 hover:bg-green-700">
                Kembali ke Beranda
            </a>
        @endif
    </div>
@endsection
