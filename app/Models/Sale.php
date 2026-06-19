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
            // Biar aman, eksekusi jika bukan penyesuaian stok ikan mati instan
            if (!str_starts_with($sale->customer_name, 'SYSTEM_LOSS_')) {
                self::prosesFifoPenjualan($sale);
            }
        });

        // 2. OTOMATISASI RE-KALIBRASI STOK (SAAT NOTA DI-EDIT)
        static::updating(function ($sale) {
            DB::transaction(function () use ($sale) {
                // Ambil data detail item kondisi LAMA langsung dari database sebelum berubah
                $oldItems = \App\Models\SaleItem::where('sale_id', $sale->id)->get();
                
                foreach ($oldItems as $item) {
                    if ($item->product_batch_id) {
                        $batch = \App\Models\ProductBatch::find($item->product_batch_id);
                        if ($batch) {
                            $batch->increment('remaining_qty', $item->qty);
                        }
                    }
                }

                // Bersihkan dulu cashflow lama biar gak double data pas kesave gres
                $sale->cashFlows()->delete();
            });
        });

        // 3. PROSES ULANG FIFO BARU (SETELAH UPDATE SELESAI)
        static::updated(function ($sale) {
            if (!str_starts_with($sale->customer_name, 'SYSTEM_LOSS_')) {
                self::prosesFifoPenjualan($sale);
            }
        });

        // 4. INTEGRASI ARUS KAS PINTAR (MENDUKUNG PENJUALAN VS KEMATIAN BARANG)
        static::saved(function ($sale) {
            // JIKA INI DATA IKAN MATI / BARANG RUSAK
            if (str_starts_with($sale->customer_name, 'SYSTEM_LOSS_')) {
                $totalKerugianModal = $sale->saleItems->sum(fn($item) => $item->qty * $item->cost_price);
                
                if ($totalKerugianModal > 0) {
                    $sale->cashFlows()->updateOrCreate(
                        ['reference_id' => $sale->id, 'reference_type' => get_class($sale)],
                        [
                            'type' => 'Expense',
                            'category' => 'Inventory Loss',
                            'amount' => $totalKerugianModal,
                            'transaction_date' => $sale->created_at,
                            'description' => "Kerugian otomatis dari pencatatan: {$sale->customer_name}",
                        ]
                    );
                    
                    $sale->quietlyUpdate(['final_amount' => $totalKerugianModal]);
                }
            } 
            // JIKA INI TRANSAKSI KASIR JUALAN NORMAL SEPERTI BIASA
            else {
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
            }
        });

        // 5. ANTI STOK GAIB: Jalankan restorasi stok sebelum record penjualan dihapus
        static::deleting(function ($sale) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($sale) {
                foreach ($sale->saleItems as $item) {
                    if ($item->product_batch_id) {
                        $batch = \App\Models\ProductBatch::find($item->product_batch_id);
                        if ($batch) {
                            $batch->increment('remaining_qty', $item->qty);
                        }
                    }
                }
                
                $sale->cashFlows()->delete();
            });
        });
    }

    /**
     * FUNGSI TERPUSAT UNTUK EKSEKUSI MESIN FIFO PENJUALAN
     */
    protected static function prosesFifoPenjualan($sale)
    {
        DB::transaction(function () use ($sale) {
            // Paksa refresh relasi biar data baru hasil edit terbaca akurat di memori PHP
            $sale->load('saleItems.product.category'); 

            foreach ($sale->saleItems as $item) {
                // 🚀 TRICK SAKTI BYPASS JASA: Cek apakah nama kategori mengandung kata 'Jasa' atau 'Service'
                $namaKategori = optional($item->product->category)->name;
                if (stripos($namaKategori, 'Jasa') !== false || stripos($namaKategori, 'Service') !== false) {
                    $item->updateQuietly([
                        'product_batch_id' => null, 
                        'cost_price' => 0,          
                    ]);
                    continue; 
                }

                // --- SISANYA ADALAH LOGIC FIFO ASLIMU YANG BADAK ---
                $qtyDibutuhkan = $item->qty;

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