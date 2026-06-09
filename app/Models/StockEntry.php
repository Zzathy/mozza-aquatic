<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockEntry extends Model
{
    /** @use HasFactory<\Database\Factories\StockEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_name', 'supplier_phone', 'supplier_address', 'entry_date', 
        'notes', 'total_amount', 'discount', 'final_amount', 
        'payment_status', 'paid_amount', 'due_amount'
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function cashFlows(): MorphMany
    {
        return $this->morphMany(CashFlow::class, 'reference');
    }

    protected static function booted()
    {
        static::saved(function ($entry) {
            if ($entry->paid_amount > 0) {
                $entry->cashFlows()->updateOrCreate(
                    ['reference_id' => $entry->id, 'reference_type' => get_class($entry)],
                    [
                        'type' => 'Expense',
                        'category' => 'Stock Purchase',
                        'amount' => $entry->paid_amount,
                        'transaction_date' => $entry->entry_date,
                        'description' => "Kulakan/Bayar nota supplier: {$entry->supplier_name}",
                    ]
                );
            } else {
                $entry->cashFlows()->delete();
            }
        });

        static::deleted(function ($entry) {
            $entry->cashFlows()->delete();
        });
    }
}
