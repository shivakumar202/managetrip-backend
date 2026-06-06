<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();

            $table->string('pricing_strategy')->nullable();

            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('markup_percentage', 8, 2)->default(0);
            $table->decimal('markup_amount', 12, 2)->default(0);

            $table->decimal('tax_percentage', 8, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->string('tax_applied_on')->nullable();

            $table->decimal('discount_percentage', 8, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);

            $table->decimal('package_cost', 12, 2)->default(0);
            $table->string('currency', 10)->nullable();

            $table->json('markups')->nullable();
            $table->json('tax')->nullable();
            $table->json('tax_applies')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_pricings');
    }
};
