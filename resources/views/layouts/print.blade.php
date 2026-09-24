<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('messages.print'))</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; }
        h1, h2, p, table { margin: 0 0 0.4rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.15rem 0; }
        .muted { color: #444; }
        .center { text-align: center; }
        .total { font-weight: bold; border-top: 1px dashed #333; padding-top: 0.35rem; }
        @media print { .no-print { display: none !important; } }
    </style>
    @yield('print_css')
</head>
<body>
    <p class="no-print" style="padding:8px;">
        <button onclick="window.print()">{{ __('messages.print') }}</button>
    </p>
    @yield('content')
</body>
</html>
