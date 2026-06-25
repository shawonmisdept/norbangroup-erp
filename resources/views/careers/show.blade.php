@extends('layouts.careers')

@section('title', $posting->title)

@section('content')
<section class="careers-hero careers-job-hero">
    <div class="careers-hero-bg" aria-hidden="true">
        <div class="careers-hero-orb careers-hero-orb-1"></div>
        <div class="careers-hero-orb careers-hero-orb-2"></div>
        <div class="careers-hero-orb careers-hero-orb-3"></div>
        <div class="careers-hero-grid"></div>
    </div>

    <div class="careers-hero-inner">
        <a href="{{ route('careers.index') }}" class="careers-job-back">← Back to all jobs</a>

        <div class="careers-hero-content careers-job-hero-content">
            @if($posting->factory?->name)
                <span class="careers-hero-badge">
                    <span class="careers-hero-badge-dot"></span>
                    {{ $posting->factory->name }}
                </span>
            @endif

            <h1 class="careers-job-hero-title">{{ $posting->title }}</h1>

            <div class="careers-job-detail-tags">
                <span class="careers-tag careers-tag-open">{{ $posting->remainingSlots() }} {{ $posting->remainingSlots() === 1 ? 'vacancy' : 'vacancies' }}</span>
                @if($posting->department)
                    <span class="careers-tag careers-tag-factory">{{ $posting->department->name }}</span>
                @endif
                @if($posting->designation)
                    <span class="careers-tag careers-tag-role">{{ $posting->designation->name }}</span>
                @endif
            </div>

            @if($posting->closes_at)
                <p class="careers-job-hero-deadline">
                    Application deadline: <strong>{{ $posting->closes_at->format('d M Y') }}</strong>
                </p>
            @endif

        </div>
    </div>
</section>

@php
    $tabs = [
        'requirements' => ['label' => 'Requirements', 'content' => $posting->requirements],
        'responsibilities' => ['label' => 'Responsibilities', 'content' => $posting->responsibilities],
        'skills' => ['label' => 'Skills & Expertise', 'content' => $posting->skills_expertise],
        'employment_status' => ['label' => 'Employment Status', 'content' => $posting->employment_status],
    ];
@endphp

<div class="careers-job-detail">
    <div class="careers-job-detail-grid">
        <div class="careers-job-detail-main">
            <div class="careers-job-tabs" data-careers-tabs>
                <div class="careers-job-tab-list" role="tablist" aria-label="Job details">
                    @foreach($tabs as $key => $tab)
                        <button
                            type="button"
                            role="tab"
                            id="tab-{{ $key }}"
                            class="careers-job-tab {{ $loop->first ? 'is-active' : '' }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            aria-controls="panel-{{ $key }}"
                            data-tab="{{ $key }}"
                        >{{ $tab['label'] }}</button>
                    @endforeach
                </div>

                <div class="careers-job-content">
                    @foreach($tabs as $key => $tab)
                        <div
                            role="tabpanel"
                            id="panel-{{ $key }}"
                            class="careers-job-tab-panel {{ $loop->first ? 'is-active' : '' }}"
                            aria-labelledby="tab-{{ $key }}"
                            @if(! $loop->first) hidden @endif
                        >
                            @if($tab['content'])
                                <div class="careers-prose">{!! $tab['content'] !!}</div>
                            @else
                                <p class="careers-tab-empty">No information has been published for this section yet.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <aside class="careers-job-sidebar">
            <div class="careers-job-sidebar-card careers-job-sidebar-card-float">
                @if($posting->closes_at)
                    <div class="careers-sidebar-deadline">
                        <span class="careers-sidebar-deadline-label">Application Deadline</span>
                        <strong>{{ $posting->closes_at->format('d M Y') }}</strong>
                    </div>
                @endif

                <a href="{{ route('careers.apply', $posting) }}" class="careers-btn careers-btn-primary w-full !py-3 !text-sm">Apply Now</a>
                <a href="{{ route('careers.index') }}" class="careers-btn careers-btn-secondary w-full !text-sm mt-2">Browse Other Jobs</a>

                <dl class="careers-sidebar-meta">
                    <div class="careers-sidebar-meta-row">
                        <dt>Vacancy</dt>
                        <dd>{{ $posting->remainingSlots() }} of {{ $posting->slots }}</dd>
                    </div>
                    @if($posting->factory?->address)
                        <div class="careers-sidebar-meta-row">
                            <dt>Location</dt>
                            <dd>{{ $posting->factory->address }}</dd>
                        </div>
                    @endif
                    @if($posting->designation)
                        <div class="careers-sidebar-meta-row">
                            <dt>Role</dt>
                            <dd>{{ $posting->designation->name }}</dd>
                        </div>
                    @endif
                    @if($posting->workerCategory)
                        <div class="careers-sidebar-meta-row">
                            <dt>Category</dt>
                            <dd>{{ $posting->workerCategory->name }}</dd>
                        </div>
                    @endif
                    @if($posting->salaryDisplay())
                        <div class="careers-sidebar-meta-row">
                            <dt>Salary</dt>
                            <dd>{{ $posting->salaryDisplay() }}</dd>
                        </div>
                    @endif
                    @if($posting->published_at)
                        <div class="careers-sidebar-meta-row">
                            <dt>Published</dt>
                            <dd>{{ $posting->published_at->format('d M Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="careers-job-sidebar-card careers-company-card">
                <h3 class="careers-company-title">Company Information</h3>
                <p class="careers-company-name">{{ $posting->factory?->name }}</p>
                @if($posting->factory?->address)
                    <p class="careers-company-address">{{ $posting->factory->address }}</p>
                @endif
                @if($posting->factory?->phone)
                    <p class="careers-company-phone">{{ $posting->factory->phone }}</p>
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-careers-tabs]').forEach(function (root) {
    var tabs = root.querySelectorAll('[role="tab"]');
    var panels = root.querySelectorAll('[role="tabpanel"]');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-tab');

            tabs.forEach(function (item) {
                var active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            panels.forEach(function (panel) {
                var active = panel.id === 'panel-' + target;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        });
    });
});
</script>
@endpush
