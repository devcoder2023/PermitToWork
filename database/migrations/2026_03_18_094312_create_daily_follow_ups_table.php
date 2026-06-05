<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_permit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('follow_up_date');
            $table->text('notes')->nullable();
            $table->boolean('is_first_follow_up')->default(false);
            $table->timestamps();

            $table->index(['work_permit_id', 'follow_up_date']);
            $table->index(['work_permit_id', 'is_first_follow_up']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_follow_ups');
    }
};
