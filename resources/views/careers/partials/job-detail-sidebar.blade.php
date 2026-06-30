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

        @php $shareUrl = urlencode($posting->publicShareUrl()); @endphp
        <div class="careers-job-share">
            <span class="careers-job-share-label">Share this job</span>
            <div class="flex gap-2">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" class="careers-btn careers-btn-secondary flex-1 !text-xs !py-2">Facebook</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener" class="careers-btn careers-btn-secondary flex-1 !text-xs !py-2">LinkedIn</a>
                <button type="button" class="careers-btn careers-btn-secondary !text-xs !py-2 !px-3" data-copy-link="{{ $posting->publicShareUrl() }}" title="Copy link">⎘</button>
            </div>
        </div>

        <dl class="careers-sidebar-meta">
            <div class="careers-sidebar-meta-row">
                <dt>Vacancy</dt>
                <dd>{{ $posting->remainingSlots() }} of {{ $posting->slots }}</dd>
            </div>
            @if($posting->factory?->name)
                <div class="careers-sidebar-meta-row">
                    <dt>Unit</dt>
                    <dd>{{ $posting->factory->name }}</dd>
                </div>
            @endif
            @if($posting->department)
                <div class="careers-sidebar-meta-row">
                    <dt>Department</dt>
                    <dd>{{ $posting->department->name }}</dd>
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
            @if($posting->shiftLabel())
                <div class="careers-sidebar-meta-row">
                    <dt>Shift</dt>
                    <dd>{{ $posting->shiftLabel() }}</dd>
                </div>
            @endif
            @if($posting->ageRequirementLabel())
                <div class="careers-sidebar-meta-row">
                    <dt>Age</dt>
                    <dd>{{ $posting->ageRequirementLabel() }}</dd>
                </div>
            @endif
            @if($posting->requiredGenderLabel() && $posting->required_gender !== 'any')
                <div class="careers-sidebar-meta-row">
                    <dt>Gender</dt>
                    <dd>{{ $posting->requiredGenderLabel() }}</dd>
                </div>
            @endif
            @if($posting->salaryDisplay())
                <div class="careers-sidebar-meta-row">
                    <dt>Salary</dt>
                    <dd class="!text-emerald-700">{{ $posting->salaryDisplay() }}</dd>
                </div>
            @endif
            @if($posting->factory?->address)
                <div class="careers-sidebar-meta-row careers-sidebar-meta-row-stack">
                    <dt>Location</dt>
                    <dd>{{ $posting->factory->address }}</dd>
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
        <h3 class="careers-company-title">About the Company</h3>
        <p class="careers-company-name">{{ $posting->factory?->name }}</p>
        @if($posting->factory?->address)
            <p class="careers-company-address">{{ $posting->factory->address }}</p>
        @endif
        @if($posting->factory?->phone)
            <p class="careers-company-phone">{{ $posting->factory->phone }}</p>
        @endif
    </div>
</aside>

@once
    @push('scripts')
    <script>
    document.querySelectorAll('[data-copy-link]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(btn.getAttribute('data-copy-link') || '');
            btn.textContent = '✓';
            setTimeout(function () { btn.textContent = '⎘'; }, 1500);
        });
    });
    </script>
    @endpush
@endonce
