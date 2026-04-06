@extends('components.admin.layout.layout-admin')

@section('header', 'Dashboard')
@section('subtitle', 'Welcome Back!')

@section('content')
    <x-dashboard.attendance-state-card :dashboardAttendanceState="$dashboardAttendanceState" />
    @include('components.dashboard.requests-overview')
@endsection

