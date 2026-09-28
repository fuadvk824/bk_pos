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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            // Hapus unique global pada phone
            $table->dropUnique(['phone']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['store_id', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'phone']);
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');

            $table->unique('phone');
        });
    }
};
