@extends('layouts.portal-public')

@section('portal-brand-label', 'Requirement')
@section('portal-brand-url', route('orders.create'))

@section('portal-main-class', 'careers-main-fluid')

@section('content')
    @yield('frontend-content')
@endsection
