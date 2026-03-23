@extends('errors.layout')

@section('title', 'Link Kadaluarsa')

@section('content')
    <div class="mb-4 text-5xl text-orange-500">410</div>
    <h1 class="mb-2 text-2xl font-bold text-gray-900">Link Sudah Kadaluarsa</h1>
    <p class="mb-6 text-gray-600">
        Tautan yang Anda akses sudah tidak berlaku atau telah kadaluarsa.
    </p>
    @if(isset($exception) && $exception->getMessage())
        <div class="px-4 py-3 mb-6 text-sm text-orange-800 bg-orange-50 border border-orange-200 rounded-lg">
            {{ $exception->getMessage() }}
        </div>
    @elseif(!empty($message))
        <div class="px-4 py-3 mb-6 text-sm text-orange-800 bg-orange-50 border border-orange-200 rounded-lg">
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
            <p>Jika Anda memerlukan bantuan, silakan hubungi administrator.</p>
        </div>
    </div>
@endsection
