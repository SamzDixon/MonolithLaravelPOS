<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'reference',
    'supplier_id',
    'store_id',
    'user_id',
    'total_cost',
    'status',
    'notes',
    'received_at',
])]
class StockReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'total_cost' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(StockReceiptItem::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}