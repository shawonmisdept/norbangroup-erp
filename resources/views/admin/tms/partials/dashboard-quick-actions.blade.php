@if(($quickActions ?? []) !== [])
    @foreach($quickActions as $action)
        <a href="{{ $action['url'] }}"
           class="erp-btn-primary !py-2 !px-3 text-xs">
            {{ $action['label'] }}
        </a>
    @endforeach
@endif
