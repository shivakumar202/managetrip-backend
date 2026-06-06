<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trip_transpors_cabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_transport_id')->constrained('trip_transports')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->integer('trip_day')->default(1);
            $table->decimal('given_price', 10, 2);
            $table->date('travel_date');
            $table->text('remarks')->nullable();

            $table->foreignId('updated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_transpors_cabs');
    }
};
