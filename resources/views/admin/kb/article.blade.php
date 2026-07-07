@extends('layouts.admin')

@section('title', $article->title_en . ' — Knowledge Base')

@section('breadcrumbs')
    <a href="{{ route('admin.kb.hub') }}" class="text-gray-600 hover:text-brand">Knowledge Base</a><span>/</span>
    <a href="{{ route('admin.kb.module', $module->code) }}" class="text-gray-600 hover:text-brand">{{ $module->label_en }}</a><span>/</span>
    <span class="text-gray-800 font-medium">{{ $submoduleLabel }}</span>
@endsection

@section('admin-content')
<div x-data="{ lang: 'bn' }">
    @include('partials.erp.page-header', [
        'title' => $submoduleLabel,
        'subtitle' => $module->label_en . ' · ' . $module->label_bn,
    ])

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <button type="button"
                @click="lang = 'bn'"
                :class="lang === 'bn' ? 'erp-btn-primary' : 'erp-btn-secondary'">
            বাংলা
        </button>
        <button type="button"
                @click="lang = 'en'"
                :class="lang === 'en' ? 'erp-btn-primary' : 'erp-btn-secondary'">
            English
        </button>

        @if($canManage)
            <a href="{{ route('admin.kb.manage.edit', $article) }}" class="erp-btn-secondary ml-auto">Edit article</a>
        @endif

        @if(!$article->is_published && $canManage)
            <span class="erp-badge bg-amber-100 text-amber-700 text-[10px]">Draft</span>
        @endif
    </div>

    <div x-show="lang === 'bn'" x-cloak class="font-anek-bangla">
        <p class="text-lg font-semibold text-gray-900 mb-4">{{ $article->title_bn }}</p>
        @if($article->summary_bn)
            <p class="text-sm text-gray-600 mb-4 leading-relaxed">{{ $article->summary_bn }}</p>
        @endif
        @include('partials.admin.kb-article-sections', ['article' => $article, 'lang' => 'bn'])
    </div>

    <div x-show="lang === 'en'" x-cloak>
        <p class="text-lg font-semibold text-gray-900 mb-4">{{ $article->title_en }}</p>
        @if($article->summary_en)
            <p class="text-sm text-gray-600 mb-4 leading-relaxed">{{ $article->summary_en }}</p>
        @endif
        @include('partials.admin.kb-article-sections', ['article' => $article, 'lang' => 'en'])
    </div>

    @if($article->updatedBy)
        <p class="text-[10px] text-gray-400 mt-6">
            Last updated by {{ $article->updatedBy->name }}
            @portalDateTime($article->updated_at)
        </p>
    @endif
</div>
@endsection

@push('styles')
<style>
    .prose ul { list-style: disc; padding-left: 1.25rem; margin: 0.5rem 0; }
    .prose ol { list-style: decimal; padding-left: 1.25rem; margin: 0.5rem 0; }
    .prose p { margin: 0.5rem 0; }
    .prose h2, .prose h3 { font-weight: 600; margin-top: 0.75rem; }
    .prose table { width: 100%; border-collapse: collapse; margin: 0.75rem 0; }
    .prose th, .prose td { border: 1px solid #e5e7eb; padding: 0.35rem 0.5rem; font-size: 0.75rem; text-align: left; }
    .prose th { background: #f9fafb; font-weight: 600; }
</style>
@endpush
