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
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete()
                ->name('transaction_items_product_id_foreign')
                ->index();

            $table->integer('quantity');
            $table->decimal('base_price', 12, 2);
            
            $table->decimal('price', 12, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2);

            $table->timestamps();

            $table->index(['transaction_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
