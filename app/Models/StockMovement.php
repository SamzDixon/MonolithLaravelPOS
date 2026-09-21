<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'store_id', 'product_id', 'quantity_delta', 'type',
        'reference_type', 'reference_id', 'user_id', 'notes',
    ];

    public function store() { return $this->belongsTo(Store::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function reference() { return $this->morphTo(); }
}
