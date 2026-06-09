<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'type', 'customer_name', 'customer_phone', 'customer_address',
        'total_amount', 'discount', 'final_amount', 'payment_status', 'received_amount', 'balance_due', 'notes'
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function cashFlows(): MorphMany
    {
        return $this->morphMany(CashFlow::class, 'reference');
    }

    protected static function booted()
    {
        static::saved(function ($sale) {
            if ($sale->received_amount > 0) {
                $sale->cashFlows()->updateOrCreate(
                    ['reference_id' => $sale->id, 'reference_type' => get_class($sale)],
                    [
                        'type' => 'Income',
                        'category' => 'Sales',
                        'amount' => $sale->received_amount,
                        'transaction_date' => $sale->created_at,
                        'description' => "Pendapatan dari invoice: {$sale->invoice_number}",
                    ]
                );
            } else {
                $sale->cashFlows()->delete();
            }
        });

        static::deleted(function ($sale) {
            $sale->cashFlows()->delete();
        });
    }
}
