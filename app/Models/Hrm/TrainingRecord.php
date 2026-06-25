<?php

namespace App\Models\Hrm;

use App\Models\Hrm\Concerns\BelongsToFactoryEmployee;
use Illuminate\Database\Eloquent\Model;

class TrainingRecord extends Model
{
    use BelongsToFactoryEmployee;

    public const TYPES = [
        'safety'     => 'Safety Induction',
        'fire'       => 'Fire Training',
        'buyer'      => 'Buyer Compliance',
        'technical'  => 'Technical Skill',
        'other'      => 'Other',
    ];

    protected $table = 'hrm_training_records';

    protected $fillable = [
        'factory_id', 'employee_id', 'training_type', 'title', 'provider',
        'training_date', 'expiry_date', 'certificate_no', 'notes', 'created_by',
    ];

    protected $casts = [
        'training_date' => 'date',
        'expiry_date'   => 'date',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->training_type] ?? ucfirst($this->training_type);
    }
}
