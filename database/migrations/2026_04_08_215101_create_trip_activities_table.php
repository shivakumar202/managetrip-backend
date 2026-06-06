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
        Schema::create('trip_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->integer('trip_day')->default(1);
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->integer('infants')->default(0);
            $table->time('activity_time')->nullable();
            $table->integer('duration')->nullable();
            $table->decimal('adult_given_price', 10, 2);
            $table->decimal('child_given_price', 10, 2)->default(0);
            $table->decimal('infant_given_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
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
        Schema::dropIfExists('trip_activities');
    }
};
