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
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('billing_contact_name')->after('is_accreditations')->nullable(); 
            $table->string('billing_address1')->after('billing_contact_name')->nullable(); 
            $table->string('billing_address2')->after('billing_address1')->nullable(); 
            $table->string('billing_city')->after('billing_address2')->nullable(); 
            $table->string('billing_postcode')->after('billing_city')->nullable(); 
            $table->string('billing_phone')->after('billing_postcode')->nullable(); 
            $table->boolean('billing_vat_register')->after('billing_phone')->default(0); 
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            //
        });
    }
};
