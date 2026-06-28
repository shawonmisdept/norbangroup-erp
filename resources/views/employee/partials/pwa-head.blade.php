<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('portal.name') }}">
@if(auth('employee')->check())
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endif
@if(config('webpush.vapid.public_key'))
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
@endif
<link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ url('/pwa/icon-192.png') }}">
