<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('No. Invoice')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Pelanggan')->searchable(),
                TextColumn::make('final_amount')->label('Total Belanja')->money('IDR', locale: 'id')->sortable(),
                TextColumn::make('received_amount')->label('Diterima')->money('IDR', locale: 'id'),
                TextColumn::make('created_at')->label('Waktu Transaksi')->dateTime()->sortable(),
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
