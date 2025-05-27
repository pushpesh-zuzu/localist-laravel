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
            $table->string('country')->after('state')->nullable();
            $table->string('postcode_new')->after('country')->nullable();
            $table->timestamp('last_login')->after('postcode_new')->nullable();
            $table->boolean('is_online')->after('postcode_new')->default(0)->comment('0 = off, 1 = on');
            $table->string('sms_notification_no')->after('is_online')->nullable();
            $table->integer('primary_category')->after('sms_notification_no')->nullable();
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
