<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_special_services', function (Blueprint $table) {
            if (Schema::hasColumn('quote_special_services', 'service_id')) {
                $table->dropForeign(['service_id']);
                $table->foreign('service_id')
                    ->references('id')
                    ->on('special_services')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quote_special_services', function (Blueprint $table) {
            if (Schema::hasColumn('quote_special_services', 'service_id')) {
                $table->dropForeign(['service_id']);
                $table->foreign('service_id')
                    ->references('id')
                    ->on('activities')
                    ->nullOnDelete();
            }
        });
    }
};
