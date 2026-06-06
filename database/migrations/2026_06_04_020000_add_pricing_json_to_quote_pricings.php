<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quote_pricings')) {
            Schema::table('quote_pricings', function (Blueprint $table) {
                if (!Schema::hasColumn('quote_pricings', 'pricing')) {
                    $table->json('pricing')->nullable()->after('tax_applies');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quote_pricings')) {
            Schema::table('quote_pricings', function (Blueprint $table) {
                if (Schema::hasColumn('quote_pricings', 'pricing')) {
                    $table->dropColumn('pricing');
                }
            });
        }
    }
};
