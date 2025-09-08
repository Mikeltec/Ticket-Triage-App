<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Smart Ticket Triage') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Vue.js App Mount Point -->
    <div id="app"></div>
    
    <!-- Loading indicator while JS loads -->
    <noscript>
        <div style="text-align: center; padding: 50px;">
            <h2>JavaScript Required</h2>
            <p>This application requires JavaScript to be enabled.</p>
        </div>
    </noscript>
</body>
</html>