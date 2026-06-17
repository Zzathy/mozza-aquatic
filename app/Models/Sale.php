<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'customer_name', 'customer_phone', 'customer_address',
        'total_amount', 'discount', 'final_amount', 'payment_status', 'paid_amount', 'due_amount', 'notes'
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
        // 1. MESIN UTAMA FIFO (SAAT NOTA BARU DIBUAT)
        static::created(function ($sale) {
            self::prosesFifoPenjualan($sale);
        });

        // 2. OTOMATISASI RE-KALIBRASI STOK (SAAT NOTA DI-EDIT)
        static::updating(function ($sale) {
            DB::transaction(function () use ($sale) {
                // Kembalikan dulu semua stok dari item lama ke batch masing-masing
                foreach ($sale->saleItems as $item) {
                    if ($item->product_batch_id) {
                        $item->productBatch()->increment('remaining_qty', $item->qty);
                    }
                }
            });
        });

        // Setelah stok di-reset oleh 'updating', picu hitung ulang FIFO baru setelah berhasil di-update
        static::updated(function ($sale) {
            self::prosesFifoPenjualan($sale);
        });

        // 3. INTEGRASI ARUS KAS (Kodingan aslimu)
        static::saved(function ($sale) {
            if ($sale->paid_amount > 0) {
                $sale->cashFlows()->updateOrCreate(
                    ['reference_id' => $sale->id, 'reference_type' => get_class($sale)],
                    [
                        'type' => 'Income',
                        'category' => 'Sales',
                        'amount' => $sale->paid_amount,
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

    /**
     * FUNGSI TERPUSAT UNTUK EKSEKUSI MESIN FIFO PENJUALAN
     */
    protected static function prosesFifoPenjualan($sale)
    {
        DB::transaction(function () use ($sale) {
            // Muat ulang relasi items terbaru agar datanya fresh jika dipicu dari aksi edit
            $sale->load('saleItems');

            foreach ($sale->saleItems as $item) {
                $qtyDibutuhkan = $item->qty;

                // Ambil antrean batch paling tua (FIFO)
                $batches = ProductBatch::where('product_id', $item->product_id)
                    ->where('remaining_qty', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($batches as $batch) {
                    if ($qtyDibutuhkan <= 0) break;

                    if ($batch->remaining_qty >= $qtyDibutuhkan) {
                        $batch->decrement('remaining_qty', $qtyDibutuhkan);
                        
                        $item->updateQuietly([
                            'product_batch_id' => $batch->id,
                            'cost_price' => $batch->buy_price,
                        ]);
                        
                        $qtyDibutuhkan = 0;
                    } else {
                        $qtyDibutuhkan -= $batch->remaining_qty;
                        
                        $item->updateQuietly([
                            'product_batch_id' => $batch->id,
                            'cost_price' => $batch->buy_price,
                        ]);

                        $batch->update(['remaining_qty' => 0]);
                    }
                }

                if ($qtyDibutuhkan > 0) {
                    throw new \Exception("Stok tidak mencukupi untuk memproses antrean FIFO produk ID {$item->product_id}!");
                }
            }
        });
    }
}
