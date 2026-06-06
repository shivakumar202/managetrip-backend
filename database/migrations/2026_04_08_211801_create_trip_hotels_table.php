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
        Schema::create('trip_hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->integer('room_count')->default(1);
            $table->integer('night')->default(1);
            $table->integer('night_count')->default(1);
            $table->integer('total_pax')->default(1);
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->integer('infants')->default(0);
            $table->integer('aweb')->default(0);
            $table->integer('cweb')->default(0);
            $table->integer('cnb')->default(0);
            $table->date('check_in');
            $table->date('check_out');
            $table->decimal('given_price', 10, 2);
            $table->decimal('given_aweb_price', 10, 2)->default(0);
            $table->decimal('given_cweb_price', 10, 2)->default(0);
            $table->decimal('given_cnb_price', 10, 2)->default(0);
            $table->foreignId('updated_by')->constrained('users')->cascadeOnDelete();
            $table->text('remarks')->nullable();

            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_hotels');
    }
};
