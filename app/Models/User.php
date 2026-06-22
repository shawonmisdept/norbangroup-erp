<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role_id', 'factory_id', 'photo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->user_code) {
                return;
            }

            do {
                $user->user_code = 'USR-' . strtoupper(Str::random(6));
            } while (static::where('user_code', $user->user_code)->exists());
        });

        static::deleting(function (User $user) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    public function canViewMaster(string $module): bool
    {
        return $this->hasPermission("masters.{$module}.view");
    }

    public function canManageMaster(string $module): bool
    {
        return $this->hasPermission("masters.{$module}.manage");
    }

    public function hasAnyMasterViewPermission(): bool
    {
        if ($this->hasPermission('masters.view')) {
            return true;
        }

        foreach (array_keys(config('masters.modules', [])) as $module) {
            if ($this->hasPermission("masters.{$module}.view")) {
                return true;
            }
        }

        return false;
    }

    public function roleLabel(): string
    {
        return $this->role?->name ?? 'Unassigned';
    }

    public function photoUrl(): ?string
    {
        return $this->photo
            ? Storage::disk('public')->url($this->photo)
            : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return strtoupper(collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
