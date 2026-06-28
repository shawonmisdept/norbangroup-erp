@props([
    'viewUrl' => null,
    'editUrl' => null,
    'destroyUrl' => null,
    'confirm' => 'Delete this record?',
])

<div class="erp-table-actions inline-flex gap-1">
    @if($viewUrl)
        <a href="{{ $viewUrl }}" class="erp-btn-sm-secondary">View</a>
    @endif
    @if($editUrl)
        <a href="{{ $editUrl }}" class="erp-btn-sm-primary">Edit</a>
    @endif
    @if($destroyUrl)
        <form method="POST" action="{{ $destroyUrl }}" class="inline">
            @csrf @method('DELETE')
            <button type="submit" class="erp-btn-sm-secondary text-red-600" data-confirm="{{ $confirm }}">Delete</button>
        </form>
    @endif
</div>
