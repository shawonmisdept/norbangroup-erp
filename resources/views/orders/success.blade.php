@extends('layouts.frontend')

@section('title', 'Requirement Submitted')

@section('frontend-content')
<div class="portal-container portal-section min-h-[60vh] flex items-center justify-center">
    <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 sm:p-10 text-center shadow-sm">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">Requirement Submitted!</h1>
        <p class="text-sm text-gray-500 mb-5 leading-relaxed">
            Thank you! We have received your requirement and sent a confirmation to your email.
            Our team will respond within 24 hours.
        </p>
        @if($ref_code)
            <div class="inline-block bg-gold-light text-gold-dark font-semibold text-sm px-5 py-2 rounded-full mb-6">
                REF: {{ $ref_code }}
            </div>
        @endif
        <a href="{{ route('orders.create') }}"
           class="block bg-brand hover:bg-brand-dark text-white text-sm font-semibold py-3 rounded-xl transition">
            Submit Another Requirement
        </a>
    </div>
</div>
@endsection
