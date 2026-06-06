<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('room_type_id')->nullable()->constrained('room_types')->nullOnDelete();
            $table->string('meal_plan')->nullable();
            $table->integer('rooms')->default(1);
            $table->integer('aweb')->default(0);
            $table->integer('cweb')->default(0);
            $table->integer('cnb')->default(0);
            $table->json('stay_nights')->nullable(); // Array of night numbers
            $table->json('pricing')->nullable(); // Pricing breakdown by night
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_hotels');
    }
};
