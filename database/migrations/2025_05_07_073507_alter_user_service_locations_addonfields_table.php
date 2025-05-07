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
        Schema::table('user_service_locations', function (Blueprint $table) {
            $table->string('city')->after('postcode')->nullable();
            $table->string('travel_time')->after('city')->nullable();
            $table->string('travel_by')->after('travel_time')->nullable();
            $table->string('type')->after('travel_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_service_locations', function (Blueprint $table) {
            //
        });
    }
};
