@extends('layouts.admin')

@section('title', $module->label_en . ' — Knowledge Base')

@section('breadcrumbs')
    <a href="{{ route('admin.kb.hub') }}" class="text-gray-600 hover:text-brand">Knowledge Base</a><span>/</span>
    <span class="text-gray-800 font-medium">{{ $module->label_en }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $module->label_en,
    'subtitle' => $module->label_bn,
    'actions' => $canManage ? view('partials.admin.kb-manage-link', [
        'label' => 'Add article',
        'route' => route('admin.kb.manage.create') . '?module=' . urlencode($module->code),
    ])->render() : null,
])

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
    @php $overview = $articles->get('overview'); @endphp
    <a href="{{ $overview ? route('admin.kb.article', [$module->code, 'overview']) : ($canManage ? route('admin.kb.manage.create') . '?module=' . urlencode($module->code) : '#') }}"
       class="erp-panel hover:border-brand/40 transition-colors group {{ ! $overview && ! $canManage ? 'opacity-50 pointer-events-none' : '' }}">
        <div class="erp-panel-body">
            <div class="flex items-start justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-900 group-hover:text-brand">Module overview</h3>
                @if($overview)
                    <span class="erp-badge bg-green-100 text-green-700 text-[10px]">Published</span>
                @elseif($canManage)
                    <span class="erp-badge bg-amber-100 text-amber-700 text-[10px]">Draft needed</span>
                @else
                    <span class="erp-badge bg-gray-100 text-gray-500 text-[10px]">Not available</span>
                @endif
            </div>
            <p class="text-[11px] text-gray-500 mt-2">General workflow for this module</p>
        </div>
    </a>

    @foreach($submodules as $key => $sub)
        @php $article = $articles->get($key); @endphp
        <a href="{{ $article ? route('admin.kb.article', [$module->code, $key]) : ($canManage ? route('admin.kb.manage.create') . '?module=' . urlencode($module->code) . '&submodule=' . urlencode($key) : '#') }}"
           class="erp-panel hover:border-brand/40 transition-colors group {{ ! $article && ! $canManage ? 'opacity-50 pointer-events-none' : '' }}">
            <div class="erp-panel-body">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-900 group-hover:text-brand">{{ $sub['label'] ?? $key }}</h3>
                    @if($article)
                        <span class="erp-badge {{ $article->is_published ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }} text-[10px]">
                            {{ $article->is_published ? 'Published' : 'Draft' }}
                        </span>
                    @elseif($canManage)
                        <span class="erp-badge bg-gray-100 text-gray-500 text-[10px]">Not written</span>
                    @endif
                </div>
                @if(! empty($sub['description']))
                    <p class="text-[11px] text-gray-500 mt-2 leading-relaxed">{{ $sub['description'] }}</p>
                @endif
            </div>
        </a>
    @endforeach
</div>
@endsection
