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
        Schema::create('stock_mutation_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('transaction_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('source_store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->foreignId('destination_store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            $table->string('status')->default('completed');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutation_histories');
    }
};
