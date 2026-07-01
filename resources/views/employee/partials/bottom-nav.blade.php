@php
    $tabs = [
        [
            'route' => 'employee.dashboard',
            'match' => 'employee.dashboard',
            'label' => 'Home',
            'icon'  => 'home',
            'tone'  => 'home',
        ],
        [
            'route' => 'employee.attendance',
            'match' => 'employee.attendance',
            'label' => 'Attendance',
            'icon'  => 'clock',
            'tone'  => 'attendance',
        ],
        [
            'route' => 'employee.leave',
            'match' => 'employee.leave*',
            'label' => 'Leave',
            'icon'  => 'calendar',
            'tone'  => 'leave',
        ],
        [
            'route' => 'employee.transport.index',
            'match' => 'employee.transport*',
            'label' => 'Transport',
            'icon'  => 'car',
            'tone'  => 'transport',
        ],
        [
            'route' => 'employee.payslips',
            'match' => 'employee.payslips*',
            'label' => 'Pay',
            'icon'  => 'wallet',
            'tone'  => 'pay',
        ],
        [
            'route' => 'employee.profile',
            'match' => 'employee.profile',
            'label' => 'Profile',
            'icon'  => 'user',
            'tone'  => 'profile',
        ],
    ];
@endphp

<nav class="emp-tabbar safe-bottom" aria-label="Main navigation">
    @foreach($tabs as $tab)
        @php $active = request()->routeIs($tab['match']); @endphp
        <a href="{{ route($tab['route']) }}"
           class="emp-tab emp-tab-{{ $tab['tone'] }} {{ $active ? 'emp-tab-active' : '' }}"
           @if($active) aria-current="page" @endif>
            <span class="emp-tab-icon">
                @include('employee.partials.tab-icon', ['icon' => $tab['icon'], 'active' => $active])
            </span>
            <span class="truncate">{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
