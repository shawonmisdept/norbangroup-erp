@extends('layouts.admin')

@section('title', $article->title_en . ' — Knowledge Base')

@section('breadcrumbs')
    <a href="{{ route('admin.kb.hub') }}" class="text-gray-600 hover:text-brand">Knowledge Base</a><span>/</span>
    <a href="{{ route('admin.kb.module', $module->code) }}" class="text-gray-600 hover:text-brand">{{ $module->label_en }}</a><span>/</span>
    <span class="text-gray-800 font-medium">{{ $submoduleLabel }}</span>
@endsection

@section('admin-content')
<div x-data="{ lang: 'en' }">
    @include('partials.erp.page-header', [
        'title' => $submoduleLabel,
        'subtitle' => $module->label_en . ' · ' . $module->label_bn,
        'actions' => ($canManage ? '<a href="' . route('admin.kb.manage.edit', $article) . '" class="erp-btn erp-btn-secondary text-xs">Edit</a>' : '')
            . (!$article->is_published && $canManage ? ' <span class="erp-badge bg-amber-100 text-amber-700 text-[10px] ml-2">Draft</span>' : ''),
    ])

    <div class="flex gap-2 mb-4">
        <button type="button"
                @click="lang = 'en'"
                :class="lang === 'en' ? 'erp-btn erp-btn-primary text-xs' : 'erp-btn erp-btn-secondary text-xs'">
            English
        </button>
        <button type="button"
                @click="lang = 'bn'"
                :class="lang === 'bn' ? 'erp-btn erp-btn-primary text-xs' : 'erp-btn erp-btn-secondary text-xs'">
            বাংলা
        </button>
    </div>

    <div class="erp-panel">
        <div class="erp-panel-body">
            <div x-show="lang === 'en'" x-cloak>
                <h1 class="text-lg font-semibold text-gray-900">{{ $article->title_en }}</h1>
                @if($article->summary_en)
                    <p class="text-sm text-gray-600 mt-2">{{ $article->summary_en }}</p>
                @endif
                @if($article->body_en)
                    <div class="prose prose-sm max-w-none mt-4 text-gray-800">{!! $article->body_en !!}</div>
                @else
                    <p class="text-sm text-gray-400 mt-4 italic">No English content yet.</p>
                @endif
            </div>

            <div x-show="lang === 'bn'" x-cloak>
                <h1 class="text-lg font-semibold text-gray-900">{{ $article->title_bn }}</h1>
                @if($article->summary_bn)
                    <p class="text-sm text-gray-600 mt-2">{{ $article->summary_bn }}</p>
                @endif
                @if($article->body_bn)
                    <div class="prose prose-sm max-w-none mt-4 text-gray-800">{!! $article->body_bn !!}</div>
                @else
                    <p class="text-sm text-gray-400 mt-4 italic">বাংলা কনটেন্ট এখনো নেই।</p>
                @endif
            </div>

            @if($article->updatedBy)
                <p class="text-[10px] text-gray-400 mt-6 pt-4 border-t border-gray-100">
                    Last updated by {{ $article->updatedBy->name }}
                    @portalDateTime($article->updated_at)
                </p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .prose ul { list-style: disc; padding-left: 1.25rem; margin: 0.5rem 0; }
    .prose ol { list-style: decimal; padding-left: 1.25rem; margin: 0.5rem 0; }
    .prose p { margin: 0.5rem 0; }
    .prose h2, .prose h3 { font-weight: 600; margin-top: 1rem; }
    .prose table { width: 100%; border-collapse: collapse; margin: 0.75rem 0; }
    .prose th, .prose td { border: 1px solid #e5e7eb; padding: 0.35rem 0.5rem; font-size: 0.75rem; }
</style>
@endpush
