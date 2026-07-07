@props(['label', 'route', 'primary' => false, 'linkClass' => null])

<a href="{{ $route }}" @class([$linkClass ?? ($primary ? 'erp-btn-primary' : 'erp-btn-secondary')])>{{ $label }}</a>
