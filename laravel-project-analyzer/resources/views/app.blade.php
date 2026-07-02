<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>Project Analyzer</title>
    @unless (app()->environment('testing'))
        @vite(['resources/assets/js/app.js'])
    @endunless
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
