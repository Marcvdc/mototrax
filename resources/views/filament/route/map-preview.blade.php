@php
    $record = $getRecord();
@endphp

<div class="fi-route-map-wrapper">
    <x-route-map :route="$record" />
    @once('route-map-assets')
        @vite(['resources/js/route-map.js'])
    @endonce
</div>
