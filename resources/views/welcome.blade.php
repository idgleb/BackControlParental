@extends('layouts.app')

@section('title', 'Bienvenido a Control Parental')

@section('content')
<div class="max-w-4xl mx-auto text-center">
    <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
        Control Parental
    </h1>
    <p class="mt-6 text-lg leading-8 text-gray-600">
        Gestiona el uso de dispositivos y aplicaciones de tus hijos de manera inteligente y segura.
    </p>
    <div class="mt-10 flex items-center justify-center gap-x-6">
        @auth
            <a href="{{ route('devices.index') }}" 
               class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Ir a Mis Dispositivos
            </a>
        @else
            <a href="{{ route('login') }}" 
               class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Iniciar Sesión
            </a>
            <a href="{{ route('register') }}" 
               class="text-sm font-semibold leading-6 text-gray-900">
                Registrarse <span aria-hidden="true">→</span>
            </a>
        @endauth
    </div>
</div>
@endsection
