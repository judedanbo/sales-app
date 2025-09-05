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
        Schema::table('users', function (Blueprint $table) {
            // Add user type field for different types of users
            $table->string('user_type')->default('staff')->after('email');

            // Add nullable school_id for school-specific users
            $table->foreignId('school_id')->nullable()->after('user_type')->constrained()->nullOnDelete();

            // Add phone number for contact information
            $table->string('phone')->nullable()->after('school_id');

            // Add profile fields
            $table->string('department')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('department');

            // Add status fields
            $table->boolean('is_active')->default(true)->after('bio');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            // Add audit fields
            $table->string('created_by')->nullable()->after('last_login_at');
            $table->string('updated_by')->nullable()->after('created_by');

            // Add indexes for performance
            $table->index(['user_type', 'is_active']);
            $table->index(['school_id', 'user_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['user_type', 'is_active']);
            $table->dropIndex(['school_id', 'user_type']);

            $table->dropColumn([
                'user_type',
                'school_id',
                'phone',
                'department',
                'bio',
                'is_active',
                'last_login_at',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
