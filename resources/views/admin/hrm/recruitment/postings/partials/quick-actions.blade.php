@php
    $canPublish = $canManage && in_array($posting->status, ['draft', 'closed', 'pending_approval'], true);
    $canClose = $canManage && $posting->status !== 'closed';
    $canReopen = $canManage && $posting->status === 'closed';
    $canApprovePost = ($canApprove ?? false) && $posting->status === 'pending_approval';
@endphp

@if($canPublish || $canClose || $canReopen || $canApprovePost || $canManage)
    <div class="flex flex-wrap gap-2 mb-4">
        @if($canPublish)
            <form method="POST" action="{{ route('admin.hrm.recruitment.postings.publish', $posting) }}">@csrf
                <button type="submit" class="erp-btn-primary !py-2 !px-4 text-xs">Publish</button>
            </form>
        @endif
        @if($canApprovePost)
            <form method="POST" action="{{ route('admin.hrm.recruitment.postings.approve', $posting) }}">@csrf
                <button type="submit" class="erp-btn-primary !py-2 !px-4 text-xs">Approve</button>
            </form>
        @endif
        @if($canReopen)
            <form method="POST" action="{{ route('admin.hrm.recruitment.postings.reopen', $posting) }}">@csrf
                <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Re-open</button>
            </form>
        @endif
        @if($canClose)
            <form method="POST" action="{{ route('admin.hrm.recruitment.postings.close', $posting) }}">@csrf
                <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Close</button>
            </form>
        @endif
        @if($canManage)
            <form method="POST" action="{{ route('admin.hrm.recruitment.postings.duplicate', $posting) }}">@csrf
                <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Duplicate</button>
            </form>
            @if(($posting->applications_count ?? 0) === 0)
                <form method="POST" action="{{ route('admin.hrm.recruitment.postings.destroy', $posting) }}" data-confirm="Delete this job posting permanently?">@csrf @method('DELETE')
                    <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs !text-red-600">Delete</button>
                </form>
            @endif
        @endif
    </div>
@endif
