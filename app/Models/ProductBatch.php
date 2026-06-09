<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    /** @use HasFactory<\Database\Factories\ProductBatchFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'stock_entry_id',  'batch_number', 'initial_qty', 'remaining_qty', 'buy_price', 'expired_date'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockEntry(): BelongsTo
    {
        return $this->belongsTo(StockEntry::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
