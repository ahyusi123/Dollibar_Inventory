<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('categories', function(Blueprint $t){$t->id();$t->string('name')->unique();$t->text('description')->nullable();$t->timestamps();});
  Schema::create('warehouses', function(Blueprint $t){$t->id();$t->string('code')->unique();$t->string('name');$t->string('location')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();});
  Schema::create('suppliers', function(Blueprint $t){$t->id();$t->string('external_id')->nullable()->index();$t->string('name');$t->string('email')->nullable();$t->string('phone')->nullable();$t->string('address')->nullable();$t->timestamps();});
  Schema::create('products', function(Blueprint $t){$t->id();$t->string('sku')->unique();$t->string('name');$t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();$t->string('unit')->default('pcs');$t->text('description')->nullable();$t->decimal('unit_cost',14,2)->default(0);$t->decimal('reorder_level',14,3)->default(0);$t->boolean('is_active')->default(true);$t->timestamps();});
  Schema::create('stock_balances', function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->foreignId('warehouse_id')->constrained()->cascadeOnDelete();$t->decimal('quantity',14,3)->default(0);$t->timestamps();$t->unique(['product_id','warehouse_id']);});
  Schema::create('stock_movements', function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->foreignId('warehouse_id')->constrained()->cascadeOnDelete();$t->string('type');$t->decimal('quantity',14,3);$t->string('reference')->nullable();$t->string('source_system')->default('inventory');$t->string('external_id')->nullable();$t->text('notes')->nullable();$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->index(['source_system','external_id']);});
  Schema::create('purchase_orders', function(Blueprint $t){$t->id();$t->string('po_number')->unique();$t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();$t->string('status')->default('draft');$t->date('order_date')->nullable();$t->date('expected_date')->nullable();$t->string('currency',3)->default('PHP');$t->decimal('total',14,2)->default(0);$t->string('crater_invoice_id')->nullable()->index();$t->text('notes')->nullable();$t->timestamps();});
  Schema::create('purchase_order_items', function(Blueprint $t){$t->id();$t->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();$t->foreignId('product_id')->constrained()->restrictOnDelete();$t->decimal('quantity',14,3);$t->decimal('unit_cost',14,2);$t->decimal('line_total',14,2);$t->timestamps();});
  Schema::table('users', function(Blueprint $t){$t->string('role')->default('inventory_admin')->after('password');});
 }
 public function down(): void {
  Schema::table('users', function(Blueprint $t){$t->dropColumn('role');});
  foreach(['purchase_order_items','purchase_orders','stock_movements','stock_balances','products','suppliers','warehouses','categories'] as $table) Schema::dropIfExists($table);
 }
};
