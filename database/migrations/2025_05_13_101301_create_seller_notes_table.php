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
        Schema::create('seller_notes', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id')->nullable();
            $table->integer('seller_id')->nullable();//loggedin user
            $table->integer('buyer_id')->nullable();//buyer id
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_notes');
    }
};
