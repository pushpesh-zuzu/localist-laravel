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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_zipcode')->after('state')->nullable(); 
            $table->boolean('is_company_website')->after('company_website')->nullable(); 
            $table->string('new_jobs')->after('country_code')->nullable(); 
            $table->boolean('social_media')->after('new_jobs')->default(0);
            $table->string('suite')->after('social_media')->nullable();
            $table->boolean('nation_wide')->after('suite')->default(0);
            $table->string('total_credit')->after('nation_wide')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
