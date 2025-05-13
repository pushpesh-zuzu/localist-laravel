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
        Schema::create('recommended_leads', function (Blueprint $table) {
            $table->id();
            $table->integer('service_id')->nullable();
            $table->integer('seller_id')->nullable();
            $table->integer('buyer_id')->nullable();
            $table->integer('lead_id')->nullable();
            $table->string('bid')->nullable();
            $table->string('distance')->nullable();
            $table->string('purchase_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommended_leads');
    }
};
