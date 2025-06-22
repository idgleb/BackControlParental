<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro - Control Parental</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8 bg-gradient-to-br from-blue-100 to-purple-100">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-10 text-center text-3xl font-bold leading-9 tracking-tight text-gray-900">
            Crea una nueva cuenta
        </h2>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white px-6 py-12 shadow-xl rounded-lg sm:px-12">
            <form method="POST" action="{{ route('register.post') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium leading-6 text-gray-900">Nombre</label>
                    <div class="mt-2">
                        <input id="name" name="name" type="text" autocomplete="name" required
                               class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                               value="{{ old('name') }}">
                    </div>
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium leading-6 text-gray-900">Correo electrónico</label>
                    <div class="mt-2">
                        <input id="email" name="email" type="email" autocomplete="email" required
                               class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                               value="{{ old('email') }}">
                    </div>
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium leading-6 text-gray-900">Contraseña</label>
                    <div class="mt-2">
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                               class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    </div>
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium leading-6 text-gray-900">Confirmar contraseña</label>
                    <div class="mt-2">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                               class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Registrarse
                    </button>
                </div>
            </form>

            <p class="mt-10 text-center text-sm text-gray-500">
                ¿Ya tienes una cuenta?
                <a href="{{ route('login') }}" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500">
                    Inicia sesión
                </a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
