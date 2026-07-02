<?php

namespace App\Http\Controllers\Employee\Transport\Concerns;

use Illuminate\Http\Request;

trait CapturesTripGpsInput
{
    /** @return array{latitude: float, longitude: float, accuracy_m?: float}|null */
    protected function tripGpsInput(Request $request): ?array
    {
        if (! $request->filled(['latitude', 'longitude'])) {
            return null;
        }

        $validated = $request->validate([
            'latitude'   => ['required', 'numeric', 'between:-90,90'],
            'longitude'  => ['required', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0'],
        ]);

        return [
            'latitude'   => (float) $validated['latitude'],
            'longitude'  => (float) $validated['longitude'],
            'accuracy_m' => isset($validated['accuracy_m']) ? (float) $validated['accuracy_m'] : null,
        ];
    }
}
