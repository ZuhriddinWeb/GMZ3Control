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
        Schema::create('parameters', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('Name');
            $table->string('ShortName');
            $table->string('NameRus')->nullable();
            $table->string('ShortNameRus')->nullable();
            $table->string('WinCC')->nullable();
            $table->integer('ServerId')->nullable();
            $table->integer('ParametrTypeID');
            $table->integer('UnitsID')->nullable();            
            $table->string('Min')->nullable();
            $table->string('Max')->nullable();
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
        Schema::dropIfExists('parameters');
    }
};
