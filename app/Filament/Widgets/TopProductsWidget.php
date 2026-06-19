<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends BaseWidget
{
    // Ganti baris yang error jadi begini:
    protected static ?string $heading = '⚡ Top 5 Produk Paling Laris (Fast Moving)';

    //  GANTI PAKAI METHOD SAKTI INI (Garansi Lolos Type Checker PHP):
    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }

    public function table(Table $table): Table
    {
        // Kita ikutkan 'id' tapi dibungkus MAX() dan di-alias menjadi 'id' biar Filament seneng
        $subQuery = DB::table('sale_items')
            ->select(
                DB::raw('MAX(sale_items.id) as id'), 
                'sale_items.product_id', 
                DB::raw('SUM(sale_items.qty) as total_qty'), 
                DB::raw('SUM(sale_items.subtotal) as total_omzet')
            )
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.customer_name', 'not like', 'SYSTEM_LOSS_%')
            ->groupBy('sale_items.product_id');

        return $table
            ->query(
                SaleItem::query()
                    ->fromSub($subQuery, 'sale_items') // Alias wajib 'sale_items' biar match sama suntikan Filament
                    ->select('sale_items.id', 'sale_items.product_id', 'sale_items.total_qty', 'sale_items.total_omzet')
            )
            ->defaultSort('total_qty', 'desc')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Nama Produk / Ikan')
                    ->weight('bold'),

                TextColumn::make('product.category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('total_qty')
                    ->label('Total Terjual (Qty)')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('total_omzet')
                    ->label('Kontribusi Omzet')
                    ->money('IDR', locale: 'id'),
            ]);
    }
}