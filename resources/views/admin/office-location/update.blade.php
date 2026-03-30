@extends($layout)

@section('header', 'Update Office Location')
@section('subtitle', 'Perbarui data kantor')

@section('content')
    @include('components.office-location.page', [
        'officeLocation' => $officeLocation,
        'pageTitle' => 'Update Office Location',
        'pageDescription' => 'Refine the office map pin, radius, and timezone without changing the existing attendance relationships tied to this office.',
        'formAction' => route($routePrefix . '.office-locations.update', $officeLocation),
        'formMethod' => 'PUT',
        'cancelRoute' => route($routePrefix . '.office-locations.index'),
        'submitLabel' => 'Update Office',
        'timezoneResolveUrl' => route($routePrefix . '.office-locations.resolve-timezone'),
    ])
@endsection
