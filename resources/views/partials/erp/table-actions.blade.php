@props([
    'viewUrl' => null,
    'viewLabel' => 'View',
    'editUrl' => null,
    'editLabel' => 'Edit',
])

<div {{ $attributes->merge(['class' => 'erp-table-actions']) }}>
    @if($viewUrl)
        <a href="{{ $viewUrl }}" class="erp-btn-sm-secondary">{{ $viewLabel }}</a>
    @endif
    @if($editUrl)
        <a href="{{ $editUrl }}" class="erp-btn-sm-primary">{{ $editLabel }}</a>
    @endif
</div>
