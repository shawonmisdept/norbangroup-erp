@php
    $detailSections = array_filter([
        'overview'          => ['label' => 'Overview', 'content' => $posting->description],
        'overview_bn'       => ['label' => 'বাংলা বিবরণ', 'content' => $posting->description_bn],
        'requirements'      => ['label' => 'Requirements', 'content' => $posting->requirements],
        'responsibilities'  => ['label' => 'Responsibilities', 'content' => $posting->responsibilities],
        'skills'            => ['label' => 'Skills & Expertise', 'content' => $posting->skills_expertise],
        'employment_status' => ['label' => 'Employment Status', 'content' => $posting->employment_status],
        'benefits'          => ['label' => 'Benefits & Perks', 'content' => $posting->benefits],
    ], fn ($section) => ! empty($section['content']));

    $daysLeft = $posting->closes_at ? now()->startOfDay()->diffInDays($posting->closes_at->startOfDay(), false) : null;
@endphp

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
            @if($posting->title_bn)
                <p class="careers-job-hero-subtitle">{{ $posting->title_bn }}</p>
            @endif

            <div class="careers-job-detail-tags">
                <span class="careers-tag careers-tag-open">
                    {{ $posting->remainingSlots() }} {{ $posting->remainingSlots() === 1 ? 'vacancy' : 'vacancies' }} open
                </span>
                @if($posting->department)
                    <span class="careers-tag careers-tag-factory">{{ $posting->department->name }}</span>
                @endif
                @if($posting->designation)
                    <span class="careers-tag careers-tag-role">{{ $posting->designation->name }}</span>
                @endif
                @if($posting->shiftLabel())
                    <span class="careers-tag careers-tag-role">{{ $posting->shiftLabel() }}</span>
                @endif
                @if($posting->workerCategory)
                    <span class="careers-tag careers-tag-role">{{ $posting->workerCategory->name }}</span>
                @endif
            </div>

            @if($posting->salaryDisplay())
                <p class="careers-job-hero-salary">{{ $posting->salaryDisplay() }}</p>
            @endif

            @if($posting->closes_at)
                <p class="careers-job-hero-deadline">
                    Apply by <strong>{{ $posting->closes_at->format('d M Y') }}</strong>
                    @if($daysLeft !== null && $daysLeft >= 0)
                        <span class="careers-job-hero-deadline-note">({{ $daysLeft === 0 ? 'Last day' : $daysLeft . ' days left' }})</span>
                    @endif
                </p>
            @endif

            <div class="careers-job-hero-actions">
                <a href="{{ route('careers.apply', $posting) }}" class="careers-btn careers-btn-hero-primary">Apply Now</a>
                <a href="{{ route('careers.track') }}" class="careers-btn careers-btn-hero-ghost">Track Application</a>
            </div>
        </div>
    </div>
</section>

@if($posting->ageRequirementLabel() || ($posting->requiredGenderLabel() && $posting->required_gender !== 'any') || $posting->rehire_eligible || $posting->published_at)
    <div class="careers-job-facts">
        @if($posting->ageRequirementLabel())
            <div class="careers-job-fact">
                <span class="careers-job-fact-label">Age</span>
                <strong>{{ $posting->ageRequirementLabel() }}</strong>
            </div>
        @endif
        @if($posting->requiredGenderLabel() && $posting->required_gender !== 'any')
            <div class="careers-job-fact">
                <span class="careers-job-fact-label">Gender</span>
                <strong>{{ $posting->requiredGenderLabel() }}</strong>
            </div>
        @endif
        @if($posting->rehire_eligible)
            <div class="careers-job-fact">
                <span class="careers-job-fact-label">Rehire</span>
                <strong>Former staff welcome</strong>
            </div>
        @endif
        @if($posting->published_at)
            <div class="careers-job-fact">
                <span class="careers-job-fact-label">Posted</span>
                <strong>{{ $posting->published_at->format('d M Y') }}</strong>
            </div>
        @endif
    </div>
@endif

<div class="careers-job-detail">
    <div class="careers-job-detail-grid">
        <div class="careers-job-detail-main">
            @if($detailSections !== [])
                <div class="careers-job-tabs" data-careers-tabs>
                    <div class="careers-job-tab-list" role="tablist" aria-label="Job details">
                        @foreach($detailSections as $key => $section)
                            <button
                                type="button"
                                role="tab"
                                id="tab-{{ $key }}"
                                class="careers-job-tab {{ $loop->first ? 'is-active' : '' }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                aria-controls="panel-{{ $key }}"
                                data-tab="{{ $key }}"
                            >{{ $section['label'] }}</button>
                        @endforeach
                    </div>

                    <div class="careers-job-content">
                        @foreach($detailSections as $key => $section)
                            <div
                                role="tabpanel"
                                id="panel-{{ $key }}"
                                class="careers-job-tab-panel {{ $loop->first ? 'is-active' : '' }}"
                                aria-labelledby="tab-{{ $key }}"
                                @if(! $loop->first) hidden @endif
                            >
                                <h2 class="careers-job-panel-title">{{ $section['label'] }}</h2>
                                <div class="careers-prose">{!! $section['content'] !!}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="careers-job-content">
                    <div class="careers-tab-empty">
                        <p class="font-semibold text-gray-700 mb-1">Details coming soon</p>
                        <p class="text-sm">You can still apply — HR will contact you with more information.</p>
                        <a href="{{ route('careers.apply', $posting) }}" class="careers-btn careers-btn-primary mt-4">Apply Now</a>
                    </div>
                </div>
            @endif
        </div>

        @include('careers.partials.job-detail-sidebar')
    </div>
</div>

<div class="careers-job-apply-bar" aria-hidden="true">
    <div class="careers-job-apply-bar-inner">
        <div class="careers-job-apply-bar-text">
            <strong>{{ $posting->title }}</strong>
            <span>{{ $posting->remainingSlots() }} {{ $posting->remainingSlots() === 1 ? 'vacancy' : 'vacancies' }} left</span>
        </div>
        <a href="{{ route('careers.apply', $posting) }}" class="careers-btn careers-btn-primary !py-2.5 !px-5">Apply</a>
    </div>
</div>

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
