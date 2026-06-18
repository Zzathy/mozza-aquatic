<?php

namespace App\Filament\Resources\StockAdjusments;

use App\Filament\Resources\StockAdjusments\Pages\ManageStockAdjusments;
use App\Models\Product;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder; // Pastikan ini ke-import

class StockAdjusmentResource extends Resource
{
    protected static ?string $model = Sale::class;

    // 🛠️ AKTIFKAN PENGATURAN NAVIGASI BIAR MUNCUL DI SIDEBAR WEBSITENYA
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;
    protected static ?string $navigationLabel = 'Penyesuaian Stok (Ikan Mati)';
    protected static ?string $modelLabel = 'Penyesuaian';
    protected static ?string $pluralModelLabel = 'Penyesuaian Stok';
    // protected static ?string $navigationGroup = 'Manajemen Stok';

    // 🛠️ LENSA FILTER: Biar cuman nampilin data kematian & kerusakan barang
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('customer_name', 'like', 'SYSTEM_LOSS_%');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('created_at')
                    ->label('Tanggal Kejadian')
                    ->required()
                    ->default(now()),

                Select::make('adjustment_type')
                    ->label('Kondisi / Alasan')
                    ->options([
                        'MATI' => 'Ikan Mati 💀',
                        'RUSAK' => 'Barang Rusak / Cacat ⚠️',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $set('customer_name', "SYSTEM_LOSS_{$state}");
                    }),

                Hidden::make('customer_name'),

                Select::make('product_id')
                    ->label('Pilih Produk / Ikan')
                    ->options(Product::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('qty')
                    ->label('Jumlah (Qty)')
                    ->numeric()
                    ->minValue(1)
                    ->required(),

                Textarea::make('notes')
                    ->label('Keterangan / Kronologi')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                    
                TextColumn::make('customer_name')
                    ->label('Jenis Penyesuaian')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'SYSTEM_LOSS_MATI' => 'Ikan Mati 💀',
                        'SYSTEM_LOSS_RUSAK' => 'Barang Rusak ⚠️',
                        default => 'Penyesuaian',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'SYSTEM_LOSS_MATI' => 'danger',
                        'SYSTEM_LOSS_RUSAK' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('saleItems.product.name')
                    ->label('Nama Produk/Ikan')
                    ->searchable(),

                TextColumn::make('saleItems.qty')
                    ->label('Jumlah Qty')
                    ->alignCenter(),

                TextColumn::make('final_amount')
                    ->label('Total Kerugian (Modal FIFO)')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(), // Sebaiknya hapus EditAction demi validitas data history mati
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStockAdjusments::route('/'),
        ];
    }
}