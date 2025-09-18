<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('class_name');
            $table->string('class_code');
            $table->integer('grade_level');
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->integer('order_sequence')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'grade_level']);
            $table->unique(['school_id', 'class_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
