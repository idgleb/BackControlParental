<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrar dispositivos</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-100">
<div class="max-w-xl mx-auto mt-10 p-6 bg-white shadow">
    <form method="POST" action="{{ route('devices.link') }}" class="space-y-4">
        @csrf
        <div>
            <label for="deviceId" class="block text-sm font-medium text-gray-700">Device ID</label>
            <input id="deviceId" name="deviceId" type="text" required class="mt-1 block w-full rounded border-gray-300" />
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Vincular dispositivo</button>
    </form>
    @if($devices->isNotEmpty())
        <h2 class="mt-6 font-semibold text-lg">Tus dispositivos</h2>
        <ul class="mt-2 list-disc list-inside">
            @foreach($devices as $device)
                <li>{{ $device->deviceId }} - {{ $device->model ?? 'Sin modelo' }}</li>
            @endforeach
        </ul>
    @endif
</div>
</body>
</html>
