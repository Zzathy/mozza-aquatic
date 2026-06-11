<?php

namespace App\Filament\Resources\StockEntries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entry_date')
                    ->label('Tgl Masuk')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->searchable()
                    ->default('Umum / Tanpa Nama'),
                TextColumn::make('payment_status')
                    ->label('Status Pay')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Lunas' => 'success',
                        'Dicicil' => 'warning',
                        'Hutang' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('final_amount')
                    ->label('Total Nota')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('due_amount')
                    ->label('Sisa Utang')
                    ->money('IDR', locale: 'id')
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('batches_count')
                    ->counts('batches')
                    ->label('Macam Produk')
                    ->suffix(' Item'),
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
            ]);
    }
}
