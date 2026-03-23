@extends('errors.layout')

@section('title', 'Server Error')

@section('content')
    <div class="mb-4 text-5xl text-red-600">500</div>
    <h1 class="mb-2 text-2xl font-bold text-gray-900">Terjadi Kesalahan Server</h1>
    <p class="mb-6 text-gray-600">
        Maaf, terjadi kesalahan pada server. Silakan coba lagi nanti.
    </p>
    @if(isset($exception) && $exception->getMessage())
        <div class="px-4 py-3 mb-6 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg">
            {{ $exception->getMessage() }}
        </div>
    @elseif(!empty($message))
        <div class="px-4 py-3 mb-6 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg">
            {{ $message }}
        </div>
    @endif
    <div class="space-y-3">
        <a href="{{ url('/') }}"
           class="inline-block px-6 py-2 text-white rounded-lg transition-colors
                  {{ auth()->check() ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' }}">
            {{ auth()->check() ? 'Kembali ke Beranda' : 'Kembali ke Login' }}
        </a>
        <div class="text-sm text-gray-500">
            <p>Jika masalah berlanjut, silakan hubungi tim teknis.</p>
        </div>
    </div>
@endsection
