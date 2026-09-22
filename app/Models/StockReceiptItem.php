<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['stock_receipt_id', 'product_id', 'quantity', 'unit_cost', 'line_total'])]
class StockReceiptItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function stockReceipt()
    {
        return $this->belongsTo(StockReceipt::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}