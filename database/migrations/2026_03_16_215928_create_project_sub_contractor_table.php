<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_sub_contractor', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sub_contractor_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['project_id', 'sub_contractor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_sub_contractor');
    }
};
