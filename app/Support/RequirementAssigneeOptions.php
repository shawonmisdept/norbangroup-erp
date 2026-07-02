<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class RequirementAssigneeOptions
{
    /** @return array<int, string> */
    public static function forOrder(?Order $order = null): array
    {
        $users = self::eligibleUsers();

        $currentId = $order?->assigned_to_user_id;

        if ($currentId && ! $users->contains('id', (int) $currentId)) {
            $current = User::query()->with(['role', 'factory'])->find($currentId);

            if ($current) {
                $users->prepend($current);
            }
        }

        return $users
            ->unique('id')
            ->mapWithKeys(fn (User $user) => [$user->id => self::label($user)])
            ->all();
    }

    /** @return Collection<int, User> */
    private static function eligibleUsers(): Collection
    {
        $roleIds = Role::query()
            ->get()
            ->filter(fn (Role $role) => $role->hasPermission('orders.update'))
            ->pluck('id');

        return User::query()
            ->with(['role', 'factory'])
            ->whereIn('role_id', $roleIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public static function label(User $user): string
    {
        $parts = array_filter([
            $user->name,
            $user->role?->name,
            $user->factory?->name,
        ]);

        return implode(' — ', $parts);
    }
}
