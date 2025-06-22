<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Control Parental')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">

<div class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ url('/') }}" class="text-xl font-bold text-indigo-600">ControlParental</a>
            <div class="flex items-center space-x-4">
                @auth
                    <span class="font-semibold text-gray-800">{{ Auth::user()->name }}</span>
                    <span class="text-gray-300">|</span>
                    <a href="{{ route('devices.index') }}" class="text-gray-600 hover:text-indigo-600">Mis Dispositivos</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-indigo-600">Cerrar sesión</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-indigo-600">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-500">Registrarse</a>
                @endauth
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-6 py-12">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white mt-auto py-6">
        <div class="container mx-auto px-6 text-center text-gray-500">
            &copy; {{ date('Y') }} Control Parental. Todos los derechos reservados.
        </div>
    </footer>

</div>

</body>
</html> 