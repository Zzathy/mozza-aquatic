<?php

namespace App\Filament\Resources\StockEntries\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1. INFO SUPPLIER
                Section::make('Informasi Supplier & Nota')
                    ->components([
                        TextInput::make('supplier_name')
                            ->label('Nama Supplier / Vendor')
                            ->placeholder('Misal: Mas Ndut Tulungagung'),
                        TextInput::make('supplier_phone')
                            ->label('No. Telepon Supplier')
                            ->tel(),
                        DatePicker::make('entry_date')
                            ->label('Tanggal Masuk')
                            ->required()
                            ->default(now()),
                        Textarea::make('supplier_address')
                            ->label('Alamat Supplier')
                            ->columnSpanFull()
                            ->rows(2),
                    ])->columns(3),

                // 2. REPEATER BATCH BARANG
                Section::make('Daftar Item & Batch Barang Masuk')
                    ->components([
                        Repeater::make('batches')
                            ->relationship('batches')
                            ->live()
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            })
                            ->components([
                                Select::make('product_id')
                                    ->label('Pilih Produk / Ikan')
                                    ->options(Product::query()->where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('initial_qty')
                                    ->label('Jumlah Masuk')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($get, $set) {
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                TextInput::make('buy_price')
                                    ->label('Harga Modal / Pcs')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($get, $set) {
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->addActionLabel('Tambah Item ke Batch')
                            ->required(),
                    ]),

                // 3. RINCIAN NOMINAL UANG
                Section::make('Rincian Pembayaran Kulakan')
                    ->components([
                        TextInput::make('total_amount')
                            ->label('Total Kotor')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->afterStateHydrated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            }),

                        TextInput::make('discount')
                            ->label('Potongan / Diskon')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            }),

                        TextInput::make('final_amount')
                            ->label('Total Bersih (Final)')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('paid_amount')
                            ->label('Jumlah Dibayar (DP/Cicil)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            }),

                        TextInput::make('due_amount')
                            ->label('Sisa Utang')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->columnSpanFull()
                            ->rows(2),
                    ])->columns(3),
            ]);
    }

    public static function updateTotals($get, $set): void
    {
        $batches = $get('batches') ?? [];
        $totalKotor = 0;
        
        foreach ($batches as $batch) {
            $qty = (int)($batch['initial_qty'] ?? 0);
            $price = (float)($batch['buy_price'] ?? 0);
            $totalKotor += ($qty * $price);
        }
        
        $discount = (float)($get('discount') ?? 0);
        $paid = (float)($get('paid_amount') ?? 0);
        
        $finalAmount = $totalKotor - $discount;
        $dueAmount = $finalAmount - $paid;
        
        $set('total_amount', $totalKotor);
        $set('final_amount', $finalAmount);
        $set('due_amount', $dueAmount < 0 ? 0 : $dueAmount);
    }
}