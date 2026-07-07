@extends('layouts.admin')

@section('title', 'Knowledge Base — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-800 font-medium">Knowledge Base</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Knowledge Base',
    'subtitle' => 'Module workflows and guides — English & Bengali',
    'actions' => $canManage ? view('partials.admin.kb-manage-link', ['label' => 'Manage articles', 'route' => route('admin.kb.manage.index')])->render() : null,
])

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
    @forelse($modules as $module)
        <a href="{{ route('admin.kb.module', $module->code) }}"
           class="erp-panel hover:border-brand/40 transition-colors group">
            <div class="erp-panel-body">
                <h3 class="text-sm font-semibold text-gray-900 group-hover:text-brand">{{ $module->label_en }}</h3>
                <p class="text-[11px] text-gray-500 mt-1">{{ $module->label_bn }}</p>
                @if($module->submodules_config)
                    <p class="text-[10px] text-gray-400 mt-2">{{ count($module->submoduleDefinitions()) }} sub-modules</p>
                @endif
            </div>
        </a>
    @empty
        <div class="col-span-full erp-panel">
            <div class="erp-panel-body text-sm text-gray-500">
                No knowledge base modules are available for your account.
            </div>
        </div>
    @endforelse
</div>
@endsection
