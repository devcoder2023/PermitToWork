<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_permits', function (Blueprint $table) {
            $table->text('suspension_reason')->nullable()->after('rejection_reason');
            $table->boolean('resumption_requested')->default(false)->after('suspension_reason');
            $table->text('resumption_note')->nullable()->after('resumption_requested');
            $table->foreignId('resumption_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('resumption_note');
            $table->timestamp('resumption_approved_at')->nullable()->after('resumption_approved_by');
            $table->text('resumption_approval_note')->nullable()->after('resumption_approved_at');
            $table->text('resumption_rejection_reason')->nullable()->after('resumption_approval_note');
            $table->text('termination_reason')->nullable()->after('resumption_rejection_reason');
            $table->foreignId('terminated_by')->nullable()->constrained('users')->nullOnDelete()->after('termination_reason');
            $table->timestamp('terminated_at')->nullable()->after('terminated_by');
        });
    }

    public function down(): void
    {
        Schema::table('work_permits', function (Blueprint $table) {
            $table->dropForeign(['resumption_approved_by']);
            $table->dropForeign(['terminated_by']);
            $table->dropColumn([
                'suspension_reason',
                'resumption_requested',
                'resumption_note',
                'resumption_approved_by',
                'resumption_approved_at',
                'resumption_rejection_reason',
                'termination_reason',
                'terminated_by',
                'terminated_at',
            ]);
        });
    }
};
