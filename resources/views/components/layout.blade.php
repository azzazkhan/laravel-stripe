<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:400,700" />
    @vite(['resources/css/tailwind.css'])
</head>
<body {{ $attributes->merge(['class' => 'font-sans antialiased w-screen min-h-screen overflow-x-hidden bg-gray-100']) }}>
    {{ $slot }}
    <script src="//unpkg.com/alpinejs" defer></script>
</body>
</html>
