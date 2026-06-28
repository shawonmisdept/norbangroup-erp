@props([
    'viewUrl' => null,
    'editRoute' => null,
    'destroyRoute' => null,
    'canManage' => false,
    'confirm' => 'Delete this record?',
])

<div class="inline-flex items-center gap-1 justify-end flex-wrap">
    @if($viewUrl)
        <button type="button" data-salary-view="{{ $viewUrl }}" class="erp-btn-sm-secondary">View</button>
    @endif
    @if($canManage && $editRoute)
        <a href="{{ $editRoute }}" class="erp-btn-sm-primary">Edit</a>
    @endif
    @if($canManage && $destroyRoute)
        <form method="POST" action="{{ $destroyRoute }}" class="inline" data-confirm="{{ $confirm }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="erp-btn-danger !py-1 !px-2 text-[11px]">Del</button>
        </form>
    @endif
</div>
