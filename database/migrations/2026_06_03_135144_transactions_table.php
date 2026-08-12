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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number')->unique();

            // kasir (WAJIB)
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete()
                ->name('transactions_store_id_foreign')
                ->index();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('subtotal', 14, 2);
            $table->decimal('shipping_cost', 14, 2)->default(0);
            $table->integer('points_used')->nullable();
            
            $table->decimal('total', 14, 2);

            $table->enum('payment_status', [
                'unpaid',
                'partial',
                'paid'
            ])->default('unpaid')->index();
            $table->enum('delivery_type', ['pickup', 'delivery'])
                ->default('pickup');
            $table->string('driver_name')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // index untuk performa laporan
            $table->index(['user_id', 'created_at']);
            $table->index(['customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
