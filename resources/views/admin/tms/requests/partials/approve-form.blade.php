<div class="erp-panel p-6">
<h3 class="font-semibold mb-3">Approve & Assign</h3>
<form method="POST" action="{{ route('admin.tms.requests.approve', $transportRequest) }}" class="space-y-3" id="approve-form">
@csrf
@include('admin.tms.requests.partials.driver-assignment-fields', [
    'drivers' => $drivers,
    'rentalDrivers' => $rentalDrivers,
    'vehicles' => $vehicles,
    'passengerCount' => $transportRequest->passenger_count,
])
<button type="submit" class="erp-btn-primary w-full">Approve</button>
</form>
</div>
@include('admin.tms.requests.partials.driver-assignment-script')
