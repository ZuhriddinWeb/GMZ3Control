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
        Schema::create('static_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('static_id');
            $table->decimal('value', 15, 2);
            $table->date('period_start_date');
            $table->date('period_end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_histories');
    }
};
