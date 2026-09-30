// Schema::create('users', function (Blueprint $table) {
//             $table->id();
//              $table->foreignId('store_id')
//                 ->nullable()
//                 ->constrained()
//                 ->cascadeOnDelete()
//                 ->index();
//             $table->string('name');
//             $table->string('username')->unique()->nullable();
//             $table->string('email')->unique();
//             $table->timestamp('email_verified_at')->nullable();
//             $table->string('password');
//             $table->rememberToken();
//             $table->timestamps();
//         });//

//         Schema::create('stores', function (Blueprint $table) {
//             $table->id();
//             $table->foreignId('company_id')
//                 ->constrained()
//                 ->cascadeOnDelete();
//             $table->string('store_code')->unique(); // whid
//             $table->string('name')->index();        // whname
//             $table->text('address')->nullable();

//             $table->timestamps();

//             $table->softDeletes();
//         });
//         //
//          Schema::create('transactions', function (Blueprint $table) {
//             $table->id();

//             $table->string('invoice_number')->unique();

//             // kasir (WAJIB)
//             $table->foreignId('user_id')
//                 ->constrained()
//                 ->cascadeOnDelete();

//             $table
//                 ->foreignId('store_id')
//                 ->constrained()
//                 ->cascadeOnDelete()
//                 ->name('transactions_store_id_foreign')
//                 ->index();

//             $table->foreignId('customer_id')
//                 ->nullable()
//                 ->constrained()
//                 ->nullOnDelete();

//             $table->decimal('subtotal', 14, 2);
//             $table->decimal('shipping_cost', 14, 2)->default(0);
//             $table->integer('points_used')->nullable();
            
//             $table->decimal('total', 14, 2);

//             $table->enum('payment_status', [
//                 'unpaid',
//                 'partial',
//                 'paid'
//             ])->default('unpaid')->index();
//             $table->enum('delivery_type', ['pickup', 'delivery'])
//                 ->default('pickup');
//             $table->string('driver_name')->nullable();
//             $table->text('notes')->nullable();

//             $table->timestamps();

//             // index untuk performa laporan
//             $table->index(['user_id', 'created_at']);
//             $table->index(['customer_id']);
//         });
//         //
//          Schema::create('transaction_items', function (Blueprint $table) {
//             $table->id();

//             $table->foreignId('transaction_id')
//                 ->constrained()
//                 ->cascadeOnDelete();

//             $table
//                 ->foreignId('product_id')
//                 ->constrained()
//                 ->cascadeOnDelete()
//                 ->name('transaction_items_product_id_foreign')
//                 ->index();

//             $table->integer('quantity');
//             $table->decimal('base_price', 12, 2);
            
//             $table->decimal('price', 12, 2);
//             $table->decimal('discount', 14, 2)->default(0);
//             $table->decimal('subtotal', 14, 2);

//             $table->timestamps();

//             $table->index(['transaction_id', 'product_id']);
//         });
//         //
//          Schema::create('payments', function (Blueprint $table) {
//             $table->id();

//             $table->foreignId('user_id')
//                 ->nullable()
//                 ->constrained()
//                 ->nullOnDelete();
//             $table->foreignId('transaction_id')
//                 ->constrained()
//                 ->cascadeOnDelete();

//             $table->decimal('amount', 14, 2);
//             $table->enum('payment_method', ['cash', 'transfer', 'qris'])->index();
            
//             $table->timestamp('paid_at')->useCurrent();
//             $table->timestamps();

//             $table->index(['transaction_id', 'paid_at']);
//         });
//         //
//  Schema::create('products', function (Blueprint $table) {
//             $table->id();
//             $table->string('product_code')->unique();

//             $table->foreignId('category_id')
//                 ->constrained()
//                 ->cascadeOnDelete();

//             $table->string('name')->index();
//             $table->text('description')->nullable();
//             $table->string('unit')->nullable();
//             $table->integer('stock_all')->default(0);
//             $table->string('image')->nullable();
//             $table->string('barcode')->nullable();

//             $table->timestamps();
//             $table->index(['category_id']);
//         });//
//   Schema::create('product_store', function (Blueprint $table) {
//             $table->id();

//             $table->foreignId('product_id')
//                 ->constrained()
//                 ->cascadeOnDelete();

//             $table->foreignId('store_id')
//                 ->constrained()
//                 ->cascadeOnDelete();

//             $table->integer('stock')->default(0);
//             $table->decimal('price_all', 12, 2);
//             $table->decimal('price', 12, 2);
//             $table->decimal('discount', 12, 2)->default(0);

//             $table->timestamps();

//             $table->unique(['product_id', 'store_id']);
//         });//

//          Schema::create('customers', function (Blueprint $table) {
//             $table->id();

//             $table->string('name')->index();
//             $table->string('phone')->unique()->nullable()->index();
//             $table->text('address')->nullable();
//             $table->integer('current_point')->default(0);

//             $table->timestamps();
//         });//
//          Schema::create('customer_points', function (Blueprint $table) {
//             $table->id();

//             $table->foreignId('customer_id')
//                 ->constrained()
//                 ->cascadeOnDelete();

//             $table->integer('points');
//             $table->enum('type', ['earn', 'redeem', 'adjustment'])->index();
//             $table->string('reference')->nullable(); // invoice number
//             $table->timestamps();
//             $table->index(['customer_id', 'created_at']);
//         });// 
//         Schema::create('point_settings', function (Blueprint $table) {
//             $table->id();

//             // contoh: setiap Rp10.000
//             $table->integer('spend_amount')->default(100000);
//             // dapat 1 poin
//             $table->integer('point_reward')->default(1000);
//             $table->integer('minimum_transaction')->default(0);

//             $table->boolean('is_active')->default(true);

//             $table->timestamps();
//         });//
      
 