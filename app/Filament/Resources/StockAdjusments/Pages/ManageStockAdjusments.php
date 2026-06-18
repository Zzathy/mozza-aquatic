<?php

namespace App\Filament\Resources\StockAdjusments\Pages;

use App\Filament\Resources\StockAdjusments\StockAdjusmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;

class ManageStockAdjusments extends ManageRecords
{
    protected static string $resource = StockAdjusmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Catat Penyesuaian')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['invoice_number'] = 'ADJ-' . strtoupper(uniqid());
                    $data['payment_status'] = 'Lunas';
                    $data['total_amount'] = 0;
                    $data['final_amount'] = 0;
                    $data['paid_amount'] = 0;
                    $data['due_amount'] = 0;
                    return $data;
                })
                ->after(function ($record, array $data) {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                        // 1. Masukkan data ke sale_items
                        $item = $record->saleItems()->create([
                            'product_id' => $data['product_id'],
                            'qty' => $data['qty'],
                            'unit_price' => 0, 
                            'subtotal' => 0,   
                        ]);

                        // 2. AMBIL ANTREAN FIFO DAN HITUNG MODAL SECARA LIVE
                        $qtyDibutuhkan = $data['qty'];
                        $totalKerugian = 0;

                        // Ambil batch tertua (FIFO)
                        $batches = \App\Models\ProductBatch::where('product_id', $data['product_id'])
                            ->where('remaining_qty', '>', 0)
                            ->orderBy('created_at', 'asc')
                            ->get();

                        foreach ($batches as $batch) {
                            if ($qtyDibutuhkan <= 0) break;

                            if ($batch->remaining_qty >= $qtyDibutuhkan) {
                                $batch->decrement('remaining_qty', $qtyDibutuhkan);
                                $totalKerugian += $qtyDibutuhkan * (float)$batch->buy_price;
                                
                                $item->updateQuietly([
                                    'product_batch_id' => $batch->id,
                                    'cost_price' => $batch->buy_price,
                                ]);
                                $qtyDibutuhkan = 0;
                            } else {
                                $qtyDibutuhkan -= $batch->remaining_qty;
                                $totalKerugian += $batch->remaining_qty * (float)$batch->buy_price;
                                
                                $item->updateQuietly([
                                    'product_batch_id' => $batch->id,
                                    'cost_price' => $batch->buy_price,
                                ]);
                                $batch->update(['remaining_qty' => 0]);
                            }
                        }

                        if ($qtyDibutuhkan > 0) {
                            throw new \Exception("Gagal mencatat! Jumlah penyesuaian melebihi total stok yang tersedia di gudang.");
                        }

                        // 3. UPDATE DATA INDUK & TEMBAK CASHFLOW KERUGIAN
                        $record->updateQuietly([
                            'final_amount' => $totalKerugian
                        ]);

                        if ($totalKerugian > 0) {
                            $record->cashFlows()->updateOrCreate(
                                ['reference_id' => $record->id, 'reference_type' => get_class($record)],
                                [
                                    'type' => 'Expense',
                                    'category' => 'Inventory Loss',
                                    'amount' => $totalKerugian,
                                    'transaction_date' => $record->created_at,
                                    'description' => "Kerugian otomatis dari pencatatan: {$record->customer_name}",
                                ]
                            );
                        }
                    });
                }),
        ];
    }
}