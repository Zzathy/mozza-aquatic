<?php

namespace App\Filament\Widgets;

use App\Models\Product; 
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 1; 
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // 🔥 Ambil product, SUM kolom remaining_qty yang ada di tabel product_batches
                Product::query()
                    ->withSum('batches', 'remaining_qty') 
                    ->having('batches_sum_remaining_qty', '<=', 5) // Filter total sisa stok <= 5
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang / Ikan')
                    ->searchable(),
                    
                // 🔥 Tampilkan hasil agregat sum-nya di sini
                Tables\Columns\TextColumn::make('batches_sum_remaining_qty') 
                    ->label('Sisa Stok Total')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null || $state === 0 => 'danger',   // Habis total = merah
                        $state <= 5 => 'warning',                       // Kritis = kuning
                        default => 'success',
                    })
                    ->size('lg')
                    ->weight('bold')
                    ->formatStateUsing(fn (?int $state): string => $state ?? '0'), 
            ])
            ->heading('⚠️ Peringatan Stok Menipis')
            ->emptyStateHeading('Stok Aman Terkendali')
            ->emptyStateDescription('Semua barang masih di atas batas aman minimum.');
    }
}