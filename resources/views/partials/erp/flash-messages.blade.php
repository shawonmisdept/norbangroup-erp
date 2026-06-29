@if(session('success'))
<div class="mb-4 erp-panel px-4 py-3 border border-emerald-200 bg-emerald-50 text-emerald-800 text-sm" role="alert">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 erp-panel px-4 py-3 border border-red-200 bg-red-50 text-red-800 text-sm" role="alert">
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 erp-panel px-4 py-3 border border-red-200 bg-red-50 text-red-800 text-sm" role="alert">
    <p class="font-semibold mb-1">Please fix the following:</p>
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
