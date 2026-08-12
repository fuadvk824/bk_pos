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
        Schema::create('store_targets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->year('year')->index();
            $table->tinyInteger('month')->index();

            $table->decimal('target_amount', 14, 2);
            $table->enum('status', ['active', 'inactive'])->default('inactive')->index();

            $table->timestamps();

            $table->unique(['store_id', 'year', 'month']);
            $table->index(['store_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_targets');
    }
};
