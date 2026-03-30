@extends($layout)

@section('header', 'Office Location Detail')
@section('subtitle', 'Detail lokasi kantor')

@section('content')
    @include('components.office-location.show-page', [
        'officeLocation' => $officeLocation,
        'assignedEmployees' => $assignedEmployees,
        'routePrefix' => $routePrefix,
    ])
@endsection
