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
        Schema::create('user_response_times', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id')->nullable();
            $table->integer('seller_id')->nullable();//loggedin user
            $table->integer('buyer_id')->nullable();//buyer clicked on seller
            $table->string('is_clicked_whatsapp')->nullable();
            $table->string('is_clicked_email')->nullable();
            $table->string('is_clicked_mobile')->nullable();
            $table->string('is_clicked_sms')->nullable();
            // $table->string('status')->nullable();
            $table->dateTime('last_seen')->nullable();
            $table->dateTime('button_clicked_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_response_times');
    }
};
