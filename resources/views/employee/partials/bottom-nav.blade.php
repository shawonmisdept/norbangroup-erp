@php
    $tabs = [
        [
            'route' => 'employee.dashboard',
            'match' => 'employee.dashboard',
            'label' => 'Home',
            'icon'  => 'home',
        ],
        [
            'route' => 'employee.attendance',
            'match' => 'employee.attendance',
            'label' => 'Attendance',
            'icon'  => 'clock',
        ],
        [
            'route' => 'employee.leave',
            'match' => 'employee.leave*',
            'label' => 'Leave',
            'icon'  => 'calendar',
        ],
        [
            'route' => 'employee.payslips',
            'match' => 'employee.payslips*',
            'label' => 'Pay',
            'icon'  => 'wallet',
        ],
        [
            'route' => 'employee.profile',
            'match' => 'employee.profile',
            'label' => 'Profile',
            'icon'  => 'user',
        ],
    ];
@endphp

<nav class="emp-tabbar safe-bottom" aria-label="Main navigation">
    @foreach($tabs as $tab)
        @php $active = request()->routeIs($tab['match']); @endphp
        <a href="{{ route($tab['route']) }}"
           class="emp-tab {{ $active ? 'emp-tab-active' : '' }}"
           @if($active) aria-current="page" @endif>
            <span class="emp-tab-icon">
                @include('employee.partials.tab-icon', ['icon' => $tab['icon'], 'active' => $active])
            </span>
            <span class="truncate">{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
