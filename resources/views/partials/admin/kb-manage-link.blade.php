@props(['label', 'route', 'primary' => false])

<a href="{{ $route }}" @class([$primary ? 'erp-btn-primary' : 'erp-btn-secondary'])>{{ $label }}</a>
