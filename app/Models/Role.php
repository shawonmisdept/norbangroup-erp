<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        if (preg_match('/^masters\.([a-z0-9-]+)\.(view|manage)$/', $permission, $matches)) {
            return in_array('masters.' . $matches[2], $permissions, true);
        }

        return false;
    }

    public static function permissionGroups(): array
    {
        $groups = [
            'Operations' => config('permissions.groups.Operations', []),
        ];

        $groups['Master Data — All Modules'] = config('permissions.master_global', []);

        foreach (config('masters.groups') as $groupName => $modules) {
            $items = [];

            foreach ($modules as $moduleKey) {
                $mod = config("masters.modules.{$moduleKey}");

                if (! $mod) {
                    continue;
                }

                $items["masters.{$moduleKey}.view"] = 'View ' . $mod['label_plural'];
                $items["masters.{$moduleKey}.manage"] = 'Manage ' . $mod['label_plural'];
            }

            if ($items !== []) {
                $groups[$groupName] = $items;
            }
        }

        $groups['Administration'] = config('permissions.groups.Administration', []);

        return $groups;
    }

    public static function permissionLabel(string $key): string
    {
        foreach (static::permissionGroups() as $permissions) {
            if (isset($permissions[$key])) {
                return $permissions[$key];
            }
        }

        return $key;
    }

    public static function permissionOptions(): array
    {
        $options = [];

        foreach (static::permissionGroups() as $permissions) {
            $options = array_merge($options, $permissions);
        }

        return $options;
    }
}
