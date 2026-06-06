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
        Schema::create('special_service_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_service_id')->constrained('special_services')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->foreignId('season_date_range_id')->constrained('season_date_ranges')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_service_pricings');
    }
};
