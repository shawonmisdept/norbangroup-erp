@extends('layouts.careers')

@section('title', 'Open Positions')

@section('content')
<section class="careers-hero">
    <div class="careers-hero-bg" aria-hidden="true">
        <div class="careers-hero-orb careers-hero-orb-1"></div>
        <div class="careers-hero-orb careers-hero-orb-2"></div>
        <div class="careers-hero-orb careers-hero-orb-3"></div>
        <div class="careers-hero-grid"></div>
    </div>

    <div class="careers-hero-inner">
        <div class="careers-hero-content">
            <span class="careers-hero-badge">
                <span class="careers-hero-badge-dot"></span>
                We're Hiring — Join Our Team
            </span>
            <h1>
                Build Your
                <span class="careers-hero-highlight">Career</span>
                <br>With Norban Group
            </h1>
            <p>Join one of Bangladesh's leading ready-made garments (RMG) manufacturers. Explore available positions, apply online in minutes, and track your application status anytime.</p>
            <div class="careers-hero-actions">
                <a href="#jobs" class="careers-btn careers-btn-hero-primary">Browse Open Jobs</a>
                <a href="{{ route('careers.track') }}" class="careers-btn careers-btn-hero-ghost">Track Application</a>
            </div>
        </div>
    </div>
</section>

<form method="GET" class="careers-search">
    <div class="careers-search-grid">
        <div class="flex-1 min-w-[200px]">
            <label class="careers-field"><span>Search jobs</span></label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Operator, supervisor, HR…" class="careers-input">
        </div>
        @if($factories->count() > 1)
            <div class="w-48">
                <label class="careers-field"><span>Factory</span></label>
                <select name="factory_id" class="careers-input">
                    <option value="">All units</option>
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button type="submit" class="careers-btn careers-btn-primary">Search</button>
    </div>
</form>

<div id="jobs" class="mt-8 mb-4 flex items-center justify-between scroll-mt-24">
    <h2 class="text-lg font-bold text-[var(--careers-navy)]">{{ $postings->total() }} Open {{ Str::plural('Position', $postings->total()) }}</h2>
    <a href="{{ route('careers.track') }}" class="text-sm text-[var(--careers-blue)] hover:underline font-medium">Already applied? Track status →</a>
</div>

<div class="careers-job-grid">
    @forelse($postings as $posting)
        <article class="careers-job-card">
            <div>
                <div class="flex flex-wrap gap-2 mb-2">
                    <span class="careers-tag careers-tag-open">{{ $posting->remainingSlots() }} opening{{ $posting->remainingSlots() !== 1 ? 's' : '' }}</span>
                    <span class="careers-tag careers-tag-factory">{{ $posting->factory?->name }}</span>
                </div>
                <h3 class="careers-job-title">{{ $posting->title }}</h3>
                <div class="careers-job-meta mt-2">
                    @if($posting->department)<span>{{ $posting->department->name }}</span>@endif
                    @if($posting->designation)<span class="careers-job-meta-dot">{{ $posting->designation->name }}</span>@endif
                    @if($posting->closes_at)<span class="careers-job-meta-dot">Closes {{ $posting->closes_at->format('d M') }}</span>@endif
                </div>
            </div>
            @if($excerpt = $posting->listingExcerpt())
                <p class="careers-job-desc">{{ $excerpt }}</p>
            @endif
            <div class="careers-job-actions">
                <a href="{{ route('careers.show', $posting) }}" class="careers-btn careers-btn-secondary flex-1">View Details</a>
                <a href="{{ route('careers.apply', $posting) }}" class="careers-btn careers-btn-primary flex-1">Apply Now</a>
            </div>
        </article>
    @empty
        <div class="careers-empty col-span-full">
            <div class="careers-empty-icon">📋</div>
            <p class="font-semibold text-gray-700">No open positions right now</p>
            <p class="text-sm text-gray-500 mt-1">Check back soon or contact HR directly.</p>
        </div>
    @endforelse
</div>

@if($postings->hasPages())
    <div class="mt-8">{{ $postings->links() }}</div>
@endif
@endsection
