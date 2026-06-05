<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_permit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('stage');
            $table->string('decision');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('work_permit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_records');
    }
};
