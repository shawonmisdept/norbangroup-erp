@php
    $tabs = [
        [
            'route' => 'rental.dashboard',
            'match' => 'rental.dashboard',
            'label' => 'Home',
            'icon'  => 'home',
            'tone'  => 'home',
        ],
        [
            'route' => 'rental.trips',
            'match' => 'rental.trips*',
            'label' => 'Trips',
            'icon'  => 'car',
            'tone'  => 'attendance',
        ],
        [
            'route' => 'rental.odometer',
            'match' => 'rental.odometer*',
            'label' => 'Daily KM',
            'icon'  => 'gauge',
            'tone'  => 'leave',
        ],
        [
            'route' => 'rental.notifications.index',
            'match' => 'rental.notifications*',
            'label' => 'Alerts',
            'icon'  => 'bell',
            'tone'  => 'pay',
        ],
    ];
@endphp

<nav class="emp-tabbar safe-bottom" aria-label="Rental driver navigation">
    @foreach($tabs as $tab)
        @php $active = request()->routeIs($tab['match']); @endphp
        <a href="{{ route($tab['route']) }}"
           class="emp-tab emp-tab-{{ $tab['tone'] }} {{ $active ? 'emp-tab-active' : '' }}"
           @if($active) aria-current="page" @endif>
            <span class="emp-tab-icon">
                @include('rental.partials.tab-icon', ['icon' => $tab['icon'], 'active' => $active])
            </span>
            <span class="truncate">{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
