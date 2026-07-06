@extends('layouts.admin')

@section('title', 'Manage KB Articles')

@section('breadcrumbs')
    <a href="{{ route('admin.kb.hub') }}" class="text-gray-600 hover:text-brand">Knowledge Base</a><span>/</span>
    <span class="text-gray-800 font-medium">Manage</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Manage articles',
    'subtitle' => 'Create and edit workflow guides',
    'actions' => '<a href="' . route('admin.kb.manage.create') . '" class="erp-btn erp-btn-primary text-xs">New article</a>',
])

<div class="erp-panel">
    <div class="erp-panel-body p-0 overflow-x-auto">
        <table class="erp-table w-full">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Sub-module</th>
                    <th>Title (EN)</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                    <tr>
                        <td class="text-xs">{{ $article->module->label_en }}</td>
                        <td class="text-xs">{{ $article->isOverview() ? 'Overview' : $article->submodule_key }}</td>
                        <td class="text-xs">{{ $article->title_en }}</td>
                        <td>
                            <span class="erp-badge text-[10px] {{ $article->is_published ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $article->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.kb.manage.edit', $article) }}" class="text-brand text-xs hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-sm text-gray-500 py-8">No articles yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($articles->hasPages())
    <div class="mt-4">{{ $articles->links() }}</div>
@endif
@endsection
