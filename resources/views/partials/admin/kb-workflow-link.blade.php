@props(['module', 'submodule' => null])

@php
    $access = app(\App\Services\KbAccessService::class);
    $kbModule = is_string($module) ? $access->findModuleByCode($module) : $module;
    $submoduleKey = $submodule ?? 'overview';
    $showLink = $kbModule
        && $access->canViewModule(auth()->user(), $kbModule)
        && $access->findPublishedArticle($kbModule, $submoduleKey === 'overview' ? null : $submoduleKey);
@endphp

@if($showLink)
    <a href="{{ route('admin.kb.article', [$kbModule->code, $submoduleKey]) }}"
       class="inline-flex items-center gap-1.5 text-xs text-brand hover:underline">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Workflow guide
    </a>
@endif
