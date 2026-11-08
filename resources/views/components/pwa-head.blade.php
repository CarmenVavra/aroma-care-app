{{-- Installable web app (PWA): manifest, icons and iOS home-screen settings. --}}
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="32x32" href="/app-icons/favicon-32.png">
<link rel="icon" type="image/png" sizes="192x192" href="/app-icons/icon-192.png">
<link rel="apple-touch-icon" href="/app-icons/apple-touch-icon.png">
<meta name="application-name" content="{{ config('app.name') }}">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
