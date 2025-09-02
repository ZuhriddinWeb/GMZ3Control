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
        Schema::create('svodka_formulas', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Agar GUID lar string bo‘lsa:
            $table->int('page_id_blog');         // GUID
            $table->uuid('param_id');         // GUID
            $table->unsignedBigInteger('sex_id');
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('group_id');

            $table->json('tokens');           // ["Pid=...|agg=...|...","+","..."]
            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('svodka_formulas');
    }
};
