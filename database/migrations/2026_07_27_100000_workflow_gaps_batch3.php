<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_recruitment_offer_letters', function (Blueprint $table) {
            $table->string('response', 20)->nullable()->after('issued_at');
            $table->timestamp('responded_at')->nullable()->after('response');
            $table->text('decline_reason')->nullable()->after('responded_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('assigned_to_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->decimal('quote_amount', 14, 2)->nullable()->after('assigned_to_user_id');
            $table->text('quote_notes')->nullable()->after('quote_amount');
            $table->timestamp('quoted_at')->nullable()->after('quote_notes');
        });

        Schema::table('hrm_issued_letters', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('issued_by');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by');
            $table->foreignId('reissued_from_id')->nullable()->after('void_reason')
                ->constrained('hrm_issued_letters')->nullOnDelete();
        });

        Schema::table('tms_maintenance_bills', function (Blueprint $table) {
            $table->timestamp('posted_to_finance_at')->nullable()->after('updated_by');
            $table->foreignId('posted_to_finance_by')->nullable()->after('posted_to_finance_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tms_maintenance_bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('posted_to_finance_by');
            $table->dropColumn('posted_to_finance_at');
        });

        Schema::table('hrm_issued_letters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reissued_from_id');
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to_user_id');
            $table->dropColumn(['quote_amount', 'quote_notes', 'quoted_at']);
        });

        Schema::table('hrm_recruitment_offer_letters', function (Blueprint $table) {
            $table->dropColumn(['response', 'responded_at', 'decline_reason']);
        });
    }
};
