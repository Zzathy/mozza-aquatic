<?php

namespace Database\Seeders;

use App\Models\ProductBatch;
use App\Models\StockEntry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StockEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bikin 5 nota kulakan tiruan
        StockEntry::factory()->count(5)->create()->each(function ($entry) {
            
            // Tiap nota, buatkan 2 sampai 4 batch produk di dalamnya
            $batches = ProductBatch::factory()->count(rand(2, 4))->create([
                'stock_entry_id' => $entry->id
            ]);

            // Hitung total kotor dari seluruh batch barang yang masuk di nota ini
            $totalKotor = $batches->sum(function ($batch) {
                return $batch->initial_qty * $batch->buy_price;
            });

            // Simulasi pembayaran: acak ada yang lunas, ada yang ngutang/dicicil
            $discount = rand(0, 1) ? 10000 : 0; // Kadang dapet diskon 10k
            $finalAmount = $totalKotor - $discount;
            
            // Acak jenis pembayaran (0 = Hutang penuh, 1 = DP/Cicil, 2 = Lunas)
            $type = rand(0, 2);
            if ($type === 2) {
                $paid = $finalAmount; // Lunas
            } elseif ($type === 1) {
                $paid = $finalAmount / 2; // DP Setengah harga
            } else {
                $paid = 0; // Hutang penuh
            }

            // Update data keuangannya ke record StockEntry induk secara riil
            $entry->update([
                'total_amount' => $totalKotor,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'paid_amount' => $paid,
                'due_amount' => $finalAmount - $paid,
            ]);
        });
    }
}
