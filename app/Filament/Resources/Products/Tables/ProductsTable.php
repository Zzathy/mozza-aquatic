<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                
                // Menghitung total sisa stok dari seluruh batch (Fitur Inti FIFO)
                TextColumn::make('batches_sum_remaining_qty')
                    ->sum('batches', 'remaining_qty')
                    ->label('Stok Saat Ini')
                    ->badge()
                    ->color(fn (string $state, $record): string => 
                        (int)$state <= $record->min_stock ? 'danger' : 'success'
                    )
                    ->suffix(' pcs'),
                    
                IconColumn::make('is_active')
                    ->label('Ready')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
