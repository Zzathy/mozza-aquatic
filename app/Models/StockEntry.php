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
        'supplier_name', 'supplier_phone', 'supplier_address', 
        'entry_date', 'notes', 'total_amount', 'discount', 
        'final_amount', 'payment_status', 'paid_amount', 'due_amount'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
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
        // A. SUNTIK STATUS PEMBAYARAN OTOMATIS SEBELUM MASUK DATABASE
        static::saving(function ($entry) {
            $paid = (float)$entry->paid_amount;
            $due = (float)$entry->due_amount;
            $final = (float)$entry->final_amount;

            if ($final == 0 || $due <= 0) {
                $entry->payment_status = 'Lunas';
            } elseif ($paid > 0 && $due > 0) {
                $entry->payment_status = 'Dicicil';
            } else {
                $entry->payment_status = 'Hutang';
            }
        });

        // B. OTOMATISASI CATATAN PENGELUARAN KAS
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

        // C. HAPUS KAS KALAU NOTA DIHAPUS
        static::deleted(function ($entry) {
            $entry->cashFlows()->delete();
        });
    }
}
