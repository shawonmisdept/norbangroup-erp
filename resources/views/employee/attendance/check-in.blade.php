@extends('layouts.employee')

@section('title', 'Check In')
@section('page-title', $nextAction === 'in' ? 'Check In' : 'Check Out')
@section('page-subtitle', $employee->employee_code)
@section('back', route('employee.attendance'))

@section('content')
<div class="space-y-4" id="checkin-app"
     data-action="{{ $nextAction }}"
     data-store-url="{{ route('employee.attendance.check-in.store') }}"
     data-gate="{{ $gate?->qr_token }}">

    @if($gate)
        <div class="emp-card border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Gate QR Detected</p>
            <p class="mt-1 text-sm font-semibold text-emerald-900">{{ $gate->name }}</p>
            @if($gate->location)
                <p class="text-xs text-emerald-700">{{ $gate->location }}</p>
            @endif
        </div>
    @endif

    @if($todayLog)
        <div class="emp-card p-4">
            <p class="text-xs text-gray-500">Today so far</p>
            <p class="mt-1 text-sm font-semibold tabular-nums text-gray-900">
                @portalTime($todayLog->check_in) – @portalTime($todayLog->check_out)
            </p>
            <span class="emp-badge mt-2 inline-block bg-gray-100 text-gray-700">{{ $todayLog->statusLabel() }}</span>
        </div>
    @endif

    <div class="emp-card overflow-hidden">
        <div class="relative aspect-[4/3] bg-gray-900">
            <video id="camera" class="h-full w-full object-cover" autoplay playsinline muted></video>
            <canvas id="snapshot" class="hidden"></canvas>
            <div id="camera-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-white/70">
                <p class="text-sm">Camera preview</p>
                <p class="mt-1 text-xs text-white/50">Tap start camera below</p>
            </div>
        </div>
        <div class="border-t border-gray-100 p-4 space-y-3">
            <button type="button" id="start-camera" class="emp-btn-secondary w-full">Start Camera</button>
            <button type="button" id="capture-photo" class="emp-btn w-full hidden">Take Selfie</button>
        </div>
    </div>

    <div class="emp-card p-4 space-y-2">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-900">Location (GPS)</p>
            <span id="gps-status" class="text-xs text-gray-400">Waiting…</span>
        </div>
        <p id="gps-coords" class="text-xs tabular-nums text-gray-500">—</p>
        <button type="button" id="refresh-gps" class="emp-btn-sm-secondary">Refresh location</button>
    </div>

    <form method="POST" action="{{ route('employee.attendance.check-in.store') }}" id="checkin-form">
        @csrf
        <input type="hidden" name="punch_type" value="{{ $nextAction }}">
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
        <input type="hidden" name="photo" id="photo" value="">
        @if($gate)
            <input type="hidden" name="gate" value="{{ $gate->qr_token }}">
        @endif

        <button type="submit" id="submit-checkin" disabled
                class="emp-btn w-full py-4 text-base disabled:opacity-40">
            {{ $nextAction === 'in' ? '✓ Check In Now' : '✓ Check Out Now' }}
        </button>
    </form>

    <p class="px-1 text-center text-[11px] text-gray-400">
        Stand near the factory gate. GPS + selfie required for mobile attendance.
    </p>
</div>

@push('scripts')
<script>
(function () {
    const app = document.getElementById('checkin-app');
    const video = document.getElementById('camera');
    const canvas = document.getElementById('snapshot');
    const photoInput = document.getElementById('photo');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const submitBtn = document.getElementById('submit-checkin');
    const gpsStatus = document.getElementById('gps-status');
    const gpsCoords = document.getElementById('gps-coords');
    let stream = null;
    let hasPhoto = false;
    let hasGps = false;

    function updateSubmit() {
        submitBtn.disabled = !(hasPhoto && hasGps);
    }

    function setGps(lat, lng) {
        latInput.value = lat;
        lngInput.value = lng;
        gpsCoords.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        gpsStatus.textContent = 'Ready';
        gpsStatus.className = 'text-xs font-semibold text-emerald-600';
        hasGps = true;
        updateSubmit();
    }

    function fetchGps() {
        gpsStatus.textContent = 'Locating…';
        if (!navigator.geolocation) {
            gpsStatus.textContent = 'GPS not supported';
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => setGps(pos.coords.latitude, pos.coords.longitude),
            () => {
                gpsStatus.textContent = 'GPS denied';
                gpsStatus.className = 'text-xs font-semibold text-red-600';
            },
            { enableHighAccuracy: true, timeout: 15000 }
        );
    }

    document.getElementById('refresh-gps').addEventListener('click', fetchGps);
    fetchGps();

    document.getElementById('start-camera').addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            video.srcObject = stream;
            document.getElementById('camera-placeholder').classList.add('hidden');
            document.getElementById('start-camera').classList.add('hidden');
            document.getElementById('capture-photo').classList.remove('hidden');
        } catch (e) {
            alert('Camera access denied. Please allow camera permission.');
        }
    });

    document.getElementById('capture-photo').addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        photoInput.value = canvas.toDataURL('image/jpeg', 0.85);
        hasPhoto = true;
        document.getElementById('capture-photo').textContent = 'Selfie captured ✓';
        updateSubmit();
    });

    document.getElementById('checkin-form').addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving…';
        if (stream) stream.getTracks().forEach(t => t.stop());
    });
})();
</script>
@endpush
@endsection
