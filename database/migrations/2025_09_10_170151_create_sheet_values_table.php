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
        Schema::create('sheet_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number_page'); // 303, 304, ...
            $table->date('for_date');
            $table->string('cell', 16);             // B6 va h.k.
            $table->double('value')->nullable();    // soddalashtirib double
            $table->timestamps();

            $table->unique(['number_page', 'for_date', 'cell'], 'uniq_val_page_date_cell');
            $table->index(['number_page', 'for_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheet_values');
    }
};
