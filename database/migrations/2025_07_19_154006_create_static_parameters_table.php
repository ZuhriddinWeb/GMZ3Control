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
        Schema::create('period_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // daily, weekly, monthly, quarterly, yearly
            $table->timestamps();
        });

        Schema::create('static_parameters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('FactoryStructureID')->nullable();
            $table->integer('ParameterID')->nullable();
            $table->integer('NumberPage')->nullable();
            $table->integer('GroupID')->nullable();
            $table->integer('OrderNumber')->nullable();
            // $table->string('name');
            $table->double('value');
            $table->foreignId('period_type_id')->constrained('period_types');
            $table->date('period_start_date');
            $table->date('period_end_date');
            // $table->string('unit')->nullable();
            // $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['name', 'period_type_id', 'period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_parameters');
        Schema::dropIfExists('period_types');
    }
};
