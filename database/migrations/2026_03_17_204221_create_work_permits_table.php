<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_permits', function (Blueprint $table) {
            $table->id();
            $table->string('permit_number')->unique();
            $table->foreignId('permit_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_contractor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('engineer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('new');
            $table->string('location_site');
            $table->string('location_area');
            $table->string('location_floor');
            $table->text('location_description')->nullable();
            $table->text('work_description');
            $table->date('request_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('shift');
            $table->json('attachments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('project_id');
            $table->index('engineer_id');
            $table->index('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_permits');
    }
};
