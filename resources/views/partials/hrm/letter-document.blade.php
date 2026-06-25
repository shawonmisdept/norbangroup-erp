@php
    $sections = app(\App\Services\Hrm\HrLetterService::class)->parseLetterSections($content);
    $factory = $factoryName ?: ($sections['factory'] ?? 'Norban Group');
@endphp

<div class="hr-letter-doc">
    <div class="hr-letter-doc-accent"></div>

    <header class="hr-letter-doc-header">
        <div>
            <p class="hr-letter-doc-org">{{ $factory }}</p>
            <p class="hr-letter-doc-dept">Human Resources Department</p>
        </div>
        @if($title ?? null)
            <span class="hr-letter-doc-badge">{{ $title }}</span>
        @endif
    </header>

    @if($referenceNo ?? null)
        <div class="hr-letter-doc-ref">
            <span>Ref: <strong>{{ $referenceNo }}</strong></span>
            @if($issuedAt ?? null)
                <span>Issued: {{ $issuedAt instanceof \DateTimeInterface ? $issuedAt->format('d M Y') : $issuedAt }}</span>
            @endif
        </div>
    @endif

    <div class="hr-letter-doc-body">
        @if($sections['date'])
            <p class="hr-letter-doc-date"><span class="hr-letter-doc-label">Date</span> {{ $sections['date'] }}</p>
        @endif

        @if(! empty($sections['to']))
            <div class="hr-letter-doc-to">
                <span class="hr-letter-doc-label">To</span>
                @foreach($sections['to'] as $line)
                    <p>{{ $line }}</p>
                @endforeach
            </div>
        @endif

        @if($sections['subject'])
            <p class="hr-letter-doc-subject">
                <span class="hr-letter-doc-label">Subject</span>
                <strong>{{ $sections['subject'] }}</strong>
            </p>
        @endif

        @if(! empty($sections['body']))
            <div class="hr-letter-doc-content">
                @foreach($sections['body'] as $paragraph)
                    <p>{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
            </div>
        @endif

        @if(! empty($sections['closing']))
            <div class="hr-letter-doc-sign">
                @foreach($sections['closing'] as $line)
                    <p>{{ $line }}</p>
                @endforeach
            </div>
        @endif
    </div>
</div>
