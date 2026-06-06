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
        Schema::dropIfExists('ferry_route');
        
        Schema::create('ferry_route', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ferry_id')->constrained('ferries')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('ferry_routes')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            
            $table->unique(['ferry_id', 'route_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ferry_route');
    }
};
