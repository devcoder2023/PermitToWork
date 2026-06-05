<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_permit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('closure_date');
            $table->timestamp('closed_at');
            $table->timestamps();

            $table->unique(['work_permit_id', 'user_id', 'closure_date'], 'daily_closures_unique');
            $table->index(['work_permit_id', 'closure_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closures');
    }
};
