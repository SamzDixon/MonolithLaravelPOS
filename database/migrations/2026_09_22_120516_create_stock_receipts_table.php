<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->string('status', 20)->default('received');
            $table->text('notes')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['store_id', 'received_at']);
            $table->index(['supplier_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipts');
    }
};