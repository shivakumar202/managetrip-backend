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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('location');
            $table->string('star');
            $table->string('contact_info');
            $table->date('check_in_time');
            $table->date('check_out_time');
            $table->string('ceb')->default('6-12');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('payment_preference')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
