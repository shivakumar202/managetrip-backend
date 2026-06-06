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
        Schema::create('ferry_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ferry_id')->constrained('ferries')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('ferry_routes')->cascadeOnDelete();
            $table->foreignId('season_date_range_id')->nullable()->constrained('season_date_ranges')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('ferry_classes')->nullOnDelete();
            $table->foreignId('pax_id')->nullable()->constrained('pax_categories')->nullOnDelete();
            $table->timestamp('departure')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ferry_pricings');
    }
};
