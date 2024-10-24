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
        Schema::create('graphics_paramenters', function (Blueprint $table) {
            $table->id();
            $table->integer('OrderNumber');
            $table->string('ParametersID');
            $table->integer('WithFormula');
            $table->integer('FactoryStructureID');
            $table->integer('BlogsID')->nullable();
            $table->integer('GrapicsID');
            $table->integer('SourceID');
            $table->integer('PageId');
            $table->dateTime('CurrentTime')->nullable();
            $table->dateTime('EndingTime')->nullable();
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
        Schema::dropIfExists('graphics_paramenters');
    }
};
