@extends('layouts.careers')

@section('title', $posting->title)

@section('meta')
    @if($posting->meta_description)
        <meta name="description" content="{{ $posting->meta_description }}">
        <meta property="og:description" content="{{ $posting->meta_description }}">
    @elseif($excerpt = $posting->listingExcerpt(160))
        <meta name="description" content="{{ $excerpt }}">
        <meta property="og:description" content="{{ $excerpt }}">
    @endif
    <meta property="og:title" content="{{ $posting->title }}">
    <meta property="og:url" content="{{ $posting->publicShareUrl() }}">
    <meta property="og:type" content="website">
@endsection

@section('content')
    @include('careers.partials.job-detail-body')
@endsection
