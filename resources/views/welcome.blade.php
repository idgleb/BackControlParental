<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
<nav class="bg-white shadow p-4 flex space-x-4">
    <a href="{{ url('/') }}" class="text-blue-500">Inicio</a>
    <a href="{{ route('devices.index') }}" class="text-blue-500">Dispositivos</a>
</nav>
<div class="container mx-auto mt-10">
    <h1 class="text-2xl font-semibold">Bienvenido</h1>
    <p class="mt-4">Usa la navegación para administrar tus dispositivos.</p>
</div>
</body>
</html>
