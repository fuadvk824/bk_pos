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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('transaction_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 14, 2);
            // $table->decimal('received_amount', 14, 2)->nullable();
            // $table->decimal('change_amount', 14, 2)->default(0);
            $table->enum('payment_method', ['cash', 'transfer', 'qris'])->index();
            
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();

            // biar cepat query histori pembayaran
            $table->index(['transaction_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
