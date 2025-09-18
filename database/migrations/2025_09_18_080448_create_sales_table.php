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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // Core transaction information
            $table->string('sale_number')->unique()->comment('Unique transaction identifier');
            $table->timestamp('sale_date')->comment('Date and time of sale');
            $table->enum('status', ['pending', 'completed', 'voided', 'refunded'])->default('pending')->comment('Transaction status');

            // Financial information
            $table->decimal('subtotal', 10, 2)->default(0)->comment('Subtotal before tax');
            $table->decimal('tax_amount', 10, 2)->default(0)->comment('Total tax amount');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('Final total amount');
            $table->enum('payment_method', ['cash', 'card', 'mobile_money', 'bank_transfer', 'other'])->default('cash')->comment('Payment method used');

            // Customer information (optional)
            $table->json('customer_info')->nullable()->comment('Customer details (name, phone, email, etc.)');

            // Relationships
            $table->foreignId('cashier_id')->constrained('users')->comment('User who processed the sale');
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete()->comment('Associated school (if applicable)');

            // Additional information
            $table->text('notes')->nullable()->comment('Additional transaction notes');
            $table->string('receipt_number')->nullable()->comment('Receipt identifier');
            $table->timestamp('voided_at')->nullable()->comment('When transaction was voided');
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who voided transaction');
            $table->text('void_reason')->nullable()->comment('Reason for voiding transaction');

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who created this sale');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who last updated this sale');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['sale_date', 'status'], 'sales_date_status_index');
            $table->index(['cashier_id', 'sale_date'], 'sales_cashier_date_index');
            $table->index(['school_id', 'sale_date'], 'sales_school_date_index');
            $table->index(['status'], 'sales_status_index');
            $table->index(['created_at'], 'sales_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
