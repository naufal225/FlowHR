@extends('components.super-admin.layout.layout-super-admin')

@section('header', 'Dashboard')
@section('subtitle', 'Welcome Back!')

@section('content')
    @include('components.dashboard.requests-overview')
@endsection

