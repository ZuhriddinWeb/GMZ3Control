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
        Schema::create('factory_structures', function (Blueprint $table) {
            $table->id();
            // $table->integer('ParentID')->nullable();
            $table->string('Name')->nullable();
            $table->string('ShortName')->nullable();
            $table->string('NameRus')->nullable();
            $table->string('ShortNameRus')->nullable();
            $table->text('Comment')->nullable();
            $table->dateTime('Created')->nullable();
            $table->string('Creator')->nullable();
            $table->dateTime('Changed')->nullable();
            $table->string('Changer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factory_structures');
    }
};
