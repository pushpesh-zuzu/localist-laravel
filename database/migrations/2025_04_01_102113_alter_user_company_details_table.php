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
            $table->string('company_logo')->after('company_sales_team')->nullable(); 
            $table->string('company_email')->after('company_logo')->nullable(); 
            $table->string('company_phone')->after('company_email')->nullable(); 
            $table->string('company_location')->after('company_phone')->nullable(); 
            $table->text('company_locaion_reason')->after('company_location')->nullable(); 
            $table->string('company_total_years')->after('company_locaion_reason')->nullable(); 
            $table->string('about_company')->after('company_total_years')->nullable(); 
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
