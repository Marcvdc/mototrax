<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>{{ $route->name }} — {{ config('app.name', 'MotoTrax') }}</title>

    @vite(['resources/css/app.css', 'resources/js/route-map.js'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50">
    <main class="max-w-4xl mx-auto px-4 py-8">
        <header class="mb-6">
            <h1 class="text-3xl font-semibold">{{ $route->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('door') }} {{ $route->user?->name ?? __('Onbekend') }}
            </p>
        </header>

        @if ($route->description)
            <section class="mb-6 prose max-w-none">
                <p>{{ $route->description }}</p>
            </section>
        @endif

        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6 text-sm">
            <div>
                <dt class="text-gray-500">{{ __('Afstand') }}</dt>
                <dd class="font-medium">{{ $route->distance ? number_format($route->distance, 1).' km' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">{{ __('Geschatte tijd') }}</dt>
                <dd class="font-medium">{{ $route->estimated_time ? $route->formatted_time : '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">{{ __('Waypoints') }}</dt>
                <dd class="font-medium">{{ $route->waypoint_count ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">{{ __('Niveau') }}</dt>
                <dd class="font-medium">
                    {{ $route->difficulty ? (\App\Models\Route::getDifficultyLevels()[$route->difficulty] ?? $route->difficulty) : '—' }}
                </dd>
            </div>
        </dl>

        <x-route-map :route="$route" :geojson="$geojson" />
    </main>
</body>
</html>
