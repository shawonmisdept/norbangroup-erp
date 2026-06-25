<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_leave_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('hrm_leave_types')->cascadeOnDelete();
            $table->foreignId('worker_category_id')->nullable()->constrained('hrm_worker_categories')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('hrm_employment_types')->nullOnDelete();
            $table->unsignedSmallInteger('min_tenure_days')->default(0);
            $table->string('gender', 10)->nullable();
            $table->boolean('allow_probation')->default(true);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['factory_id', 'leave_type_id'], 'hrm_leave_rule_factory_type_idx');
        });

        Schema::create('hrm_maternity_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('total_weeks')->default(16);
            $table->unsignedTinyInteger('paid_weeks')->default(8);
            $table->unsignedTinyInteger('unpaid_weeks')->default(8);
            $table->unsignedSmallInteger('min_service_days')->default(180);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('factory_id', 'hrm_maternity_rule_factory_uq');
        });

        Schema::create('hrm_salary_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 80);
            $table->string('head_type', 20)->default('earning');
            $table->string('calculation_type', 20)->default('fixed');
            $table->boolean('is_taxable')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'code'], 'hrm_salary_head_factory_code_uq');
        });

        Schema::create('hrm_salary_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 80);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'code'], 'hrm_salary_grade_factory_code_uq');
        });

        Schema::create('hrm_salary_grade_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_grade_id')->constrained('hrm_salary_grades')->cascadeOnDelete();
            $table->foreignId('salary_head_id')->constrained('hrm_salary_heads')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->foreignId('percentage_of_head_id')->nullable()->constrained('hrm_salary_heads')->nullOnDelete();
            $table->timestamps();

            $table->unique(['salary_grade_id', 'salary_head_id'], 'hrm_salary_grade_detail_uq');
        });

        Schema::table('hrm_salary_structures', function (Blueprint $table) {
            $table->foreignId('salary_grade_id')->nullable()->after('employee_id')->constrained('hrm_salary_grades')->nullOnDelete();
        });

        $this->syncRolePermissions();
    }

    public function down(): void
    {
        Schema::table('hrm_salary_structures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salary_grade_id');
        });

        Schema::dropIfExists('hrm_salary_grade_details');
        Schema::dropIfExists('hrm_salary_grades');
        Schema::dropIfExists('hrm_salary_heads');
        Schema::dropIfExists('hrm_maternity_rules');
        Schema::dropIfExists('hrm_leave_rules');
    }

    private function syncRolePermissions(): void
    {
        $salaryPerms = ['hrm.salary.view', 'hrm.salary.manage', 'hrm.salary.approve'];
        $payrollMap = [
            'hrm.payroll.view'    => 'hrm.salary.view',
            'hrm.payroll.manage'  => 'hrm.salary.manage',
            'hrm.payroll.approve' => 'hrm.salary.approve',
        ];

        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];

            foreach ($payrollMap as $legacy => $modern) {
                if (in_array($legacy, $permissions, true) && ! in_array($modern, $permissions, true)) {
                    $permissions[] = $modern;
                }
            }

            if ($role->name === 'Administrator') {
                $permissions = array_values(array_unique(array_merge($permissions, $salaryPerms)));
            } elseif ($role->name === 'Manager') {
                if (! in_array('hrm.salary.view', $permissions, true)) {
                    $permissions[] = 'hrm.salary.view';
                }
            }

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique($permissions))),
                'updated_at'  => now(),
            ]);
        }
    }
};
