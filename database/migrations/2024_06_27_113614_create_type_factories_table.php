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
        Schema::create('type_factories', function (Blueprint $table) {
            $table->id();
            $table->string('Name');
            $table->string('ShortName');
            $table->string('NameRus')->nullable();
            $table->string('ShortNameRus')->nullable();
            $table->integer('OrderNumberSex');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_factories');
    }
};
