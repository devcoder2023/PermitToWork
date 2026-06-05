<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('execution_engineer')->after('email');
            $table->foreignId('project_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('sub_contractor_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('sub_contractor_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['sub_contractor_id']);
            $table->dropColumn(['role', 'project_id', 'sub_contractor_id', 'phone']);
        });
    }
};
