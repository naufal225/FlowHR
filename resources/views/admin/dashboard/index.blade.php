@extends('components.admin.layout.layout-admin')

@section('header', 'Dashboard')
@section('subtitle', 'Welcome Back!')

@section('content')
    @include('components.dashboard.requests-overview')
@endsection

