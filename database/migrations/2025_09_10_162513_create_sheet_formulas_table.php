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
        Schema::create('sheet_formulas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('param_id')->nullable();   
            $table->integer('number_page');
            $table->date('date')->nullable();       // istasang null – doimiy formula
            $table->string('cell', 12);
            $table->text('expr');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheet_formulas');
    }
};
