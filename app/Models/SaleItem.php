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
        // TRIK SAKTI: Hitung subtotal otomatis sebelum masuk ke MySQL
        static::creating(function ($item) {
            $item->subtotal = (int)$item->qty * (float)$item->unit_price;
        });

        // Jika item penjualan dihapus, kembalikan qty-nya ke batch asalnya
        static::deleted(function ($item) {
            if ($item->product_batch_id) {
                $item->productBatch()->increment('remaining_qty', $item->qty);
            }
        });
    }
}
