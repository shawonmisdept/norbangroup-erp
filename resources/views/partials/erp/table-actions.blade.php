@props([
    'viewUrl' => null,
    'viewLabel' => 'View',
    'editUrl' => null,
    'editLabel' => 'Edit',
    'destroyUrl' => null,
    'destroyLabel' => 'Delete',
    'destroyConfirm' => 'Delete this record?',
])

<div {{ $attributes->merge(['class' => 'erp-table-actions']) }}>
    @if($viewUrl)
        <a href="{{ $viewUrl }}" class="erp-btn-sm-secondary">{{ $viewLabel }}</a>
    @endif
    @if($editUrl)
        <a href="{{ $editUrl }}" class="erp-btn-sm-primary">{{ $editLabel }}</a>
    @endif
    @if($destroyUrl)
        <form method="POST" action="{{ $destroyUrl }}" class="inline" data-confirm="{{ $destroyConfirm }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="erp-btn-sm-secondary !text-red-600">{{ $destroyLabel }}</button>
        </form>
    @endif
</div>
