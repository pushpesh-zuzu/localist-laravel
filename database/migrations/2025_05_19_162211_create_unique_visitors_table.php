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
        Schema::create('unique_visitors', function (Blueprint $table) {
            $table->id();
            $table->integer('seller_id')->nullable();
            $table->integer('buyer_id')->nullable();
            $table->integer('lead_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('date')->nullable();
            $table->integer('visitors_count')->nullable();
            $table->integer('random_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unique_visitors');
    }
};
