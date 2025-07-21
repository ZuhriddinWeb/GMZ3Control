<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_number_pages', function (Blueprint $table) {
            $table->id();
            $table->integer('IdBlog')->nullable();
            $table->integer('IdNumberPage')->nullable();
            $table->string('Name')->nullable();
            $table->string('NameRus')->nullable();
            $table->json('FactoryStructureID')->nullable();
            $table->json('NumberPageBlogs')->nullable();
            $table->string('Comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_number_pages');
    }
};
