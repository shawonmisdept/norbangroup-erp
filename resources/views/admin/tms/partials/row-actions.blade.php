@props([
    'viewUrl' => null,
    'viewModalUrl' => null,
    'editUrl' => null,
    'destroyUrl' => null,
    'confirm' => 'Delete this record?',
])

<div class="erp-table-actions inline-flex gap-1">
    @if($viewModalUrl)
        <button type="button" data-tms-view="{{ $viewModalUrl }}" class="erp-btn-sm-secondary">View</button>
    @elseif($viewUrl)
        <a href="{{ $viewUrl }}" class="erp-btn-sm-secondary">View</a>
    @endif
    @if($editUrl)
        <a href="{{ $editUrl }}" class="erp-btn-sm-primary">Edit</a>
    @endif
    @if($destroyUrl)
        <form method="POST" action="{{ $destroyUrl }}" class="inline" data-confirm="{{ $confirm }}">
            @csrf @method('DELETE')
            <button type="submit" class="erp-btn-sm-secondary text-red-600">Delete</button>
        </form>
    @endif
</div>
