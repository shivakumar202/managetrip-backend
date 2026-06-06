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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('query_id')->constrained()->cascadeOnDelete();
            $table->string('quote_code')->unique();
            $table->integer('basic_details_change')->default(0);
            $table->integer('adult_count')->default(1);
            $table->integer('child_count')->default(0);
            $table->integer('infant_count')->default(0);
            $table->date('travel_date');
            $table->decimal('hotel_total', 10, 2);
            $table->decimal('activity_total', 10, 2);
            $table->decimal('transport_total', 10, 2);
            $table->decimal('extra_total', 10, 2);
            $table->decimal('base_price', 10, 2);
            $table->decimal('discount_percentage', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->integer('validity_days')->default(30);
            $table->date('expiry_date');
            $table->integer('tax_applied')->default(0);
            $table->string('tax_applied_on');
            $table->decimal('markup_percentage', 10, 2);
            $table->decimal('markup_amount', 10, 2);
            $table->decimal('tax_percentage', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('package_cost', 10, 2);
            $table->string('currency')->default('INR');
            $table->text('remarks')->nullable();
            $table->foreignId('updated_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected','upgraded'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
