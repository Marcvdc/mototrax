<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $user->name }}
            </h2>
            <a href="{{ route('profile.edit') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                Profiel bewerken
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('new_token'))
                <div class="bg-green-50 border border-green-400 rounded-lg p-4">
                    <p class="font-semibold text-green-800 mb-1">Token aangemaakt — kopieer hem nu, je ziet hem maar één keer:</p>
                    <code class="block bg-green-100 text-green-900 px-3 py-2 rounded text-sm break-all">{{ session('new_token') }}</code>
                </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ([
                    ['label' => 'Motoren', 'value' => $user->bikes->count()],
                    ['label' => 'Routes', 'value' => $user->routes->count()],
                    ['label' => 'Onderhoudslogs', 'value' => $user->bikes->sum(fn($b) => $b->maintenanceLogs->count())],
                    ['label' => 'API tokens', 'value' => $tokens->count()],
                ] as $stat)
                    <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                        <div class="text-3xl font-bold text-indigo-600">{{ $stat['value'] }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ $stat['label'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Mijn motoren</h3>
                </div>
                @if ($user->bikes->isEmpty())
                    <p class="px-6 py-4 text-gray-500 text-sm">Nog geen motoren toegevoegd. Ga naar <a href="/admin/bikes/create" class="text-indigo-600 underline">admin</a> om er een toe te voegen.</p>
                @else
                    <div class="divide-y">
                        @foreach ($user->bikes as $bike)
                            <div class="px-6 py-4 flex items-center gap-4">
                                @if ($bike->image)
                                    <img src="{{ asset('storage/' . $bike->image) }}" class="w-16 h-16 object-cover rounded-lg">
                                @else
                                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs text-center leading-tight p-1">{{ $bike->brand }}</div>
                                @endif
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800">{{ $bike->brand }} {{ $bike->model }}</div>
                                    <div class="text-sm text-gray-500">
                                        @if ($bike->year) {{ $bike->year }} &middot; @endif
                                        {{ number_format($bike->km_current ?? 0) }} km
                                    </div>
                                </div>
                                <div class="text-sm text-gray-400">
                                    {{ $bike->maintenanceLogs->count() }} log(s)
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Mijn routes</h3>
                </div>
                @if ($user->routes->isEmpty())
                    <p class="px-6 py-4 text-gray-500 text-sm">Nog geen routes gedeeld. Ga naar <a href="/admin/routes/create" class="text-indigo-600 underline">admin</a> om een GPX te uploaden.</p>
                @else
                    <div class="divide-y">
                        @foreach ($user->routes->take(5) as $route)
                            <div class="px-6 py-4 flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-800">{{ $route->name }}</div>
                                    @if (!empty($route->tags))
                                        <div class="flex gap-1 mt-1 flex-wrap">
                                            @foreach ((array) $route->tags as $tag)
                                                <span class="bg-indigo-50 text-indigo-700 text-xs px-2 py-0.5 rounded-full">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ asset('storage/' . $route->gpx_file) }}" class="text-sm text-indigo-600 hover:underline" download>GPX</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">API Tokens</h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <form method="POST" action="{{ route('profile.tokens.create') }}" class="flex gap-3">
                        @csrf
                        <input type="text" name="token_name" placeholder="Token naam (bijv. Postman)" required
                            class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-md hover:bg-indigo-700">
                            Aanmaken
                        </button>
                    </form>
                    @error('token_name')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                    @if ($tokens->isEmpty())
                        <p class="text-gray-500 text-sm">Nog geen tokens aangemaakt.</p>
                    @else
                        <ul class="divide-y border rounded-md">
                            @foreach ($tokens as $token)
                                <li class="flex items-center justify-between px-4 py-3">
                                    <div>
                                        <span class="font-medium text-sm text-gray-800">{{ $token->name }}</span>
                                        <span class="text-xs text-gray-400 ml-2">aangemaakt {{ $token->created_at->diffForHumans() }}</span>
                                        @if ($token->last_used_at)
                                            <span class="text-xs text-gray-400 ml-2">&middot; gebruikt {{ $token->last_used_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('profile.tokens.delete', $token->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 text-xs hover:text-red-700">Intrekken</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
