@props(['label', 'route', 'primary' => false, 'class' => null])

<a href="{{ $route }}" @class([$class ?? ($primary ? 'erp-btn-primary' : 'erp-btn-secondary')])>{{ $label }}</a>
