@extends($layout)

@section('header', 'Add Office Location')
@section('subtitle', 'Tambah data kantor')

@section('content')
    @include('components.office-location.page', [
        'pageTitle' => 'Add Office Location',
        'pageDescription' => 'Create a professional office location profile with visual pin placement, attendance radius preview, and automatic timezone handling.',
        'formAction' => route($routePrefix . '.office-locations.store'),
        'formMethod' => 'POST',
        'cancelRoute' => route($routePrefix . '.office-locations.index'),
        'submitLabel' => 'Save Office',
        'timezoneResolveUrl' => route($routePrefix . '.office-locations.resolve-timezone'),
    ])
@endsection
