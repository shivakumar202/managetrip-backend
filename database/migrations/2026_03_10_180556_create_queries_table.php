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
        Schema::create('queries', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('destination');
            $table->string('reference_id')->nullable();
            $table->string('sales_team_id');
            $table->text('tag_id')->nullable();
            $table->string('source_contact_person')->nullable();
            $table->date('start_date')->nullable();
            $table->integer('nights')->nullable();
            $table->integer('adults')->nullable();
            $table->integer('children')->nullable();
            $table->text('children_ages')->nullable();
            $table->string('salutation')->nullable();
            $table->string('name')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->string('origin')->nullable();
            $table->string('nationality')->nullable();
            $table->text('comments')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queries');
    }
};
