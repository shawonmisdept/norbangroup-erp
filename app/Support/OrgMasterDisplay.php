<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Designation;

class OrgMasterDisplay
{
    public static function department(?Department $department): string
    {
        return RelationDisplay::label($department, 'name', 'factory.name');
    }

    public static function designation(?Designation $designation): string
    {
        if (! $designation) {
            return '';
        }

        $designation->loadMissing('department.factory');

        $parts = array_filter([
            $designation->name,
            $designation->department?->name,
            $designation->department?->factory?->name,
        ]);

        return implode(' — ', $parts);
    }

    public static function shift(?\App\Models\Hrm\Shift $shift): string
    {
        return RelationDisplay::label($shift, 'name', 'factory.name');
    }
}
