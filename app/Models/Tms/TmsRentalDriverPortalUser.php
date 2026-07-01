<?php

namespace App\Models\Tms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class TmsRentalDriverPortalUser extends Authenticatable
{
    use HasPushSubscriptions;
    use Notifiable;

    protected $table = 'tms_rental_driver_portal_users';

    protected $fillable = [
        'rental_driver_id',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function rentalDriver(): BelongsTo
    {
        return $this->belongsTo(TmsRentalDriver::class, 'rental_driver_id');
    }

    public function canLogin(): bool
    {
        if (! $this->is_active || ! $this->rentalDriver) {
            return false;
        }

        return $this->rentalDriver->isActive();
    }
}
