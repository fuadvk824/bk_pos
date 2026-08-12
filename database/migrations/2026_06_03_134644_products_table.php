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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('unit')->nullable();
            $table->integer('stock_all')->default(0);
            $table->string('image')->nullable();
            $table->string('barcode')->nullable();

            $table->timestamps();
            $table->index(['category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
