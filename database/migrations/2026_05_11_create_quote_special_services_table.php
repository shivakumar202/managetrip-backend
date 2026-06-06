<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_special_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->string('type'); // 'extra_sightseeing' or 'other_service'
            $table->foreignId('service_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('service_name')->nullable();
            $table->integer('day')->nullable();
            $table->text('notes')->nullable();
            $table->json('pricing')->nullable(); // For extras/other services pricing
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_special_services');
    }
};
