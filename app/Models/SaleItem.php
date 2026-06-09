<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    /** @use HasFactory<\Database\Factories\SaleItemFactory> */
    use HasFactory;

    protected $fillable = ['sale_id', 'product_id', 'product_batch_id', 'qty', 'cost_price', 'unit_price', 'subtotal'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }

    protected static function booted()
    {
        static::creating(function ($item) {
            $batch = ProductBatch::where('product_id', $item->product_id)
                ->where('remaining_qty', '>', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($batch) {
                $item->product_batch_id = $batch->id;
                $item->cost_price = $batch->buy_price;
                
                $batch->decrement('remaining_qty', $item->qty);
            }
        });

        static::deleted(function ($item) {
            if ($item->product_batch_id) {
                $item->productBatch()->increment('remaining_qty', $item->qty);
            }
        });
    }
}
