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
        Schema::create('number_pages', function (Blueprint $table) {
            $table->id();
            $table->integer('StructureID');
            $table->integer('BlogID')->nullable();
            $table->integer('NumberPage');
            $table->string('Name');
            $table->string('NameRus');
            $table->text('Comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_pages');
    }
};
