<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrarse</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
<div class="max-w-md mx-auto mt-10 p-6 bg-white shadow">
    <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input id="name" name="name" type="text" required class="mt-1 block w-full rounded border-gray-300" value="{{ old('name') }}" />
            @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
            <input id="email" name="email" type="email" required class="mt-1 block w-full rounded border-gray-300" value="{{ old('email') }}" />
            @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input id="password" name="password" type="password" required class="mt-1 block w-full rounded border-gray-300" />
            @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 block w-full rounded border-gray-300" />
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Registrar</button>
    </form>
</div>
</body>
</html>
