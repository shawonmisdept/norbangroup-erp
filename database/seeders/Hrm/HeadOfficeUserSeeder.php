<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HeadOfficeUserSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{factory: string, employees: list<array<string, mixed>>} $data */
        $data = require database_path('seeders/data/head_office_employees.php');

        $factory = Factory::query()
            ->where('name', $data['factory'])
            ->where('is_active', true)
            ->first();

        if (! $factory) {
            $this->command?->warn("Factory \"{$data['factory']}\" not found — skipping Head Office user seed.");

            return;
        }

        $roleByEmployeeCode = collect($data['employees'])
            ->mapWithKeys(fn (array $row) => [
                (string) $row['employee_code'] => trim((string) ($row['role'] ?? '')),
            ]);

        $defaultPassword = (string) config('hrm.head_office_default_user_password', 'password');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $employees = Employee::query()
            ->where('factory_id', $factory->id)
            ->whereIn('status', ['active', 'probation'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('employee_code')
            ->get();

        foreach ($employees as $employee) {
            $email = $this->normalizeEmail($employee->email);

            if ($email === null) {
                $skipped++;

                continue;
            }

            $roleKey = $roleByEmployeeCode[$employee->employee_code] ?? '';

            if ($roleKey === '' || $roleKey === 'No Need') {
                $skipped++;

                continue;
            }

            $role = Role::query()->where('name', $roleKey)->first();

            if (! $role) {
                $this->command?->warn("Role \"{$roleKey}\" not found — skipped user for {$email}.");
                $skipped++;

                continue;
            }

            $attributes = [
                'name'       => $employee->name,
                'role_id'    => $role->id,
                'factory_id' => $factory->id,
            ];

            $user = User::query()->where('email', $email)->first();

            if ($user) {
                $user->fill($attributes);
                $user->save();
                $updated++;
            } else {
                User::create(array_merge($attributes, [
                    'email'    => $email,
                    'password' => $defaultPassword,
                ]));
                $created++;
            }
        }

        $this->command?->info(sprintf(
            'Head Office users: %d created, %d updated, %d skipped (no valid email or no admin role). Default password: %s',
            $created,
            $updated,
            $skipped,
            $defaultPassword
        ));
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $email = Str::lower(trim((string) $email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }
}
