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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id')->nullable();
            $table->integer('from_user_id')->nullable();//loggedin user
            $table->integer('to_user_id')->nullable();//buyer clicked on seller
            $table->string('activity_name')->nullable();
            $table->string('contact_type')->nullable();
            $table->string('duration')->nullable()->comment('in hour');
            $table->string('duration_minutes')->nullable()->comment('in minute');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
