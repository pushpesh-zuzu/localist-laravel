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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'));
            $table->string('page_title')->nullable();
            $table->string('page_menu')->nullable();
            $table->string('category_id')->nullable();
            $table->string('slug')->unique();
            $table->text('title_desc')->nullable();
            $table->text('page_details')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('og_image')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_keyword')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('page_script')->nullable();
            $table->string('lower_section_title')->nullable();
            $table->text('lower_section_desc')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
