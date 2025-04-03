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
            $table->text('company_photos')->after('is_autobid')->nullable(); 
            $table->text('user_emails_reviews')->after('company_photos')->nullable(); 
            $table->boolean('is_youtube_video')->after('user_emails_reviews')->default(1); 
            $table->string('company_youtube_link')->after('is_youtube_video')->nullable(); 
            $table->boolean('is_fb')->after('company_youtube_link')->default(1); 
            $table->string('fb_link')->after('is_fb')->nullable(); 
            $table->boolean('is_twitter')->after('fb_link')->default(1); 
            $table->string('twitter_link')->after('is_twitter')->nullable(); 
            $table->boolean('is_link_desc')->after('twitter_link')->default(1); 
            $table->text('link_desc')->after('is_link_desc')->nullable(); 
            $table->boolean('is_accreditations')->after('link_desc')->default(1); 
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
