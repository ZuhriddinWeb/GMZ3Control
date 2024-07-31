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
        Schema::create('values_parameters', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('ParametersID')->nullable();
            $table->integer('SourcesID')->nullable();
            $table->integer('TimeID')->nullable();
            $table->integer('BlogID')->nullable();
            $table->double('Value')->nullable();
            $table->text('Comment')->nullable();
            $table->integer('GraphicsTimesID')->nullable();
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
        Schema::dropIfExists('values_parameters');
    }
};
