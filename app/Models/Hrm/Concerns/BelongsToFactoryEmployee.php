<?php

namespace App\Models\Hrm\Concerns;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToFactoryEmployee
{
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
