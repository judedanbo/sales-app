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
        Schema::create('class_product_requirements', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('school_id')->constrained()->cascadeOnDelete()->comment('School this requirement belongs to');
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete()->comment('Academic year for this requirement');
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete()->comment('School class this requirement is for');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('Required product');

            // Requirement details
            $table->boolean('is_required')->default(true)->comment('Whether this product is required or optional');
            $table->integer('min_quantity')->default(1)->comment('Minimum quantity required');
            $table->integer('max_quantity')->nullable()->comment('Maximum quantity allowed (null = no limit)');
            $table->integer('recommended_quantity')->nullable()->comment('Recommended quantity');

            // Timing and availability
            $table->date('required_by')->nullable()->comment('Date by which this product must be obtained');
            $table->boolean('is_active')->default(true)->comment('Whether this requirement is currently active');
            $table->text('description')->nullable()->comment('Additional details about the requirement');
            $table->text('notes')->nullable()->comment('Internal notes');

            // Pricing and budgeting
            $table->decimal('estimated_cost', 10, 2)->nullable()->comment('Estimated cost per unit');
            $table->decimal('budget_allocation', 10, 2)->nullable()->comment('Budget allocated for this requirement');

            // Priority and categorization
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->comment('Requirement priority');
            $table->string('requirement_category')->nullable()->comment('Category of requirement (uniform, books, supplies, etc.)');

            // Approval and management
            $table->foreignId('created_by')->constrained('users')->comment('User who created this requirement');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who approved this requirement');
            $table->datetime('approved_at')->nullable()->comment('When this requirement was approved');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['school_id', 'academic_year_id', 'class_id'], 'class_requirements_school_lookup');
            $table->index(['product_id', 'is_required'], 'class_requirements_product_lookup');
            $table->index(['is_active', 'priority'], 'class_requirements_status_lookup');
            $table->index(['required_by'], 'class_requirements_deadline_lookup');

            // Unique constraint to prevent duplicate requirements
            $table->unique(['school_id', 'academic_year_id', 'class_id', 'product_id'], 'class_requirements_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_product_requirements');
    }
};
