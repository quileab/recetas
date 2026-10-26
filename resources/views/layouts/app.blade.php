<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200/50 dark:bg-base-200">

    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <a href="/items" title="Recetas Pyme" class="flex items-center gap-2 ml-5 pt-5">
                <span class="sr-only">Recetas Pyme</span>
                <span class="bg-gradient-to-br from-amber-400 to-orange-600 rounded-xl p-2 shadow-md shadow-orange-200 inline-flex">
                    <x-icon name="lucide.chef-hat" class="w-6 h-6 text-white" />
                </span>
            </a>
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-icon name="lucide.menu" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main full-width>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

            {{-- BRAND --}}
            <a href="/items" title="Recetas Pyme" class="hidden lg:flex items-center justify-center py-6 mb-2">
                <span class="sr-only">Recetas Pyme</span>
                <span class="bg-gradient-to-br from-amber-400 to-orange-600 rounded-2xl p-3 shadow-lg shadow-orange-200 inline-flex ring-1 ring-orange-300/50">
                    <x-icon name="lucide.chef-hat" class="w-8 h-8 text-white" />
                </span>
            </a>

            {{-- MENU --}}
            <x-menu activate-by-route>
                <x-menu-item title="Dashboard" icon="lucide.layout-dashboard" icon-classes="text-sky-500" link="/" />
                <x-menu-sub title="Catálogos" icon="lucide.library-big" icon-classes="text-amber-500">
                    <x-menu-item title="Ítems y Costos" icon="lucide.shopping-basket" icon-classes="text-emerald-500" link="/items" />
                </x-menu-sub>
                <x-menu-sub title="Producción" icon="lucide.cooking-pot" icon-classes="text-orange-500">
                    <x-menu-item title="Recetas" icon="lucide.soup" icon-classes="text-orange-600" link="/recipes" />
                </x-menu-sub>
            </x-menu>

        </x-slot:sidebar>

        {{-- THE CONTENT --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />
</body>
</html>
