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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('school_code')->unique();
            $table->string('school_name');
            $table->string('school_type');
            $table->string('board_affiliation')->nullable();
            $table->date('established_date')->nullable();
            $table->string('status', 10)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'school_type']);
            $table->index('school_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
