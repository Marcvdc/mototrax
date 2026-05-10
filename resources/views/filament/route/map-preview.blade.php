@php
    $record = $getRecord();
@endphp

<div class="fi-route-map-wrapper">
    <x-route-map :route="$record" />
    @vite(['resources/css/app.css', 'resources/js/route-map.js'])
</div>
