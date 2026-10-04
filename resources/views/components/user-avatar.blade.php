@props(['user', 'size' => 'h-8 w-8 text-xs'])

@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" {{ $attributes->merge(['class' => "$size rounded-full object-cover"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$size inline-flex items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700"]) }}>
        {{ collect(explode(' ', $user->name))->filter()->take(2)->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
    </span>
@endif
