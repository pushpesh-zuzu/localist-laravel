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
        Schema::create('user_card_details', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('card_number')->nullable();
            $table->string('expiry_date')->nullable();
            $table->string('cvc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_card_details');
    }
};
