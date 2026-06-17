<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // SECTION 1: HEADER NOTA PENJUALAN
                // SECTION 1: HEADER NOTA PENJUALAN (Bersih dari Type)
                Section::make('Header Transaksi Kasir')
                    ->components([
                        TextInput::make('invoice_number')
                            ->label('No. Invoice')
                            ->required()
                            ->default('INV-' . date('Ymd-Hi') . '-' . rand(100, 999)),
                        TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->default('Umum / Cash'),
                        TextInput::make('customer_phone')
                            ->label('No. HP Pelanggan'),
                        Textarea::make('customer_address')
                            ->label('Alamat Pelanggan')
                            ->rows(2)->columnSpanFull(),
                    ])->columns(3), // Diubah jadi 3 kolom karena type sudah dihapus

                // SECTION 2: REPEATER BELANJAAN (Bersih & Fokus Otomatisasi Harga)
                Section::make('Daftar Belanjaan Pelanggan')
                    ->components([
                        Repeater::make('saleItems')
                            ->relationship('saleItems')
                            ->live()
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            })
                            ->components([
                                Select::make('product_id')
                                    ->label('Pilih Produk')
                                    ->options(Product::query()->where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->price); 
                                                $set('qty', 1);
                                            }
                                        }
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(2),

                                TextInput::make('qty')
                                    ->label('Qty Beli')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($get, $set) {
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label('Harga Jual / Pcs')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($get, $set) {
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),
                                    
                                // Kolom subtotal lama yang bikin penuh sudah DIHAPUS TOTAL dari sini!
                            ])->columns(4)->required(),
                    ]),

                // SECTION 3: KALKULATOR PEMBAYARAN KASIR (SINKRON DENGAN STOCK ENTRIES)
                Section::make('Total & Pembayaran')
                    ->components([
                        TextInput::make('total_amount')
                            ->label('Total Kotor')
                            ->numeric()
                            ->disabled()->dehydrated()
                            ->afterStateHydrated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            }),
                        TextInput::make('discount')
                            ->label('Diskon Nota')
                            ->numeric()->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            }),
                        TextInput::make('final_amount')
                            ->label('Total Akhir (Net)')
                            ->numeric()->disabled()->dehydrated(),
                        TextInput::make('paid_amount') // REPLACEMENT
                            ->label('Jumlah Dibayar')
                            ->numeric()->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            }),
                        TextInput::make('due_amount') // REPLACEMENT
                            ->label('Sisa Piutang / Bon')
                            ->numeric()->disabled()->dehydrated(),
                        Textarea::make('notes')
                            ->label('Catatan Transaksi')
                            ->rows(2),
                    ])->columns(5),
            ]);
    }

    public static function updateTotals($get, $set): void
    {
        $items = $get('saleItems') ?? [];
        $totalKotor = 0;

        foreach ($items as $key => $item) {
            $productId = $item['product_id'] ?? null;
            $qty = (int)($item['qty'] ?? 0);
            
            // Ambil nama produk buat ngecek apakah ini Glowfish
            $productName = $productId ? \App\Models\Product::find($productId)?->name : '';

            // --- LOGIKA KHUSUS PROMO GLOWFISH (10K DAPET 3) ---
            if (str_contains(strtolower($productName), 'glowfish')) {
                $hargaEceran = 4000; // Sesuai harga eceran standar tokomu
                
                $jumlahPaket = floor($qty / 3); // Hitung ada berapa kelipatan 3
                $sisaEceran = $qty % 3;         // Sisa ikan yang gak masuk paket
                
                // Rumus Subtotal Paket
                $subtotalItem = ($jumlahPaket * 10000) + ($sisaEceran * $hargaEceran);
                
                // Set harga unit_price secara otomatis biar databasemu gak error (Subtotal / Qty)
                $unitPrice = $qty > 0 ? $subtotalItem / $qty : $hargaEceran;
                
                // Langsung suntik nilainya ke baris repeater yang sedang aktif
                $set("saleItems.{$key}.unit_price", $unitPrice);
                $set("saleItems.{$key}.subtotal", $subtotalItem);
                
            } else {
                // --- UNTUK PRODUK SELAIN GLOWFISH (NORMAL) ---
                $price = (float)($item['unit_price'] ?? 0);
                $subtotalItem = $qty * $price;
                
                $set("saleItems.{$key}.subtotal", $subtotalItem);
            }

            $totalKotor += $subtotalItem;
        }

        // Ganti baris kalkulasi bawah nota di fungsi updateTotals dengan ini:
        $discount = (float)($get('discount') ?? 0);
        $paid = (float)($get('paid_amount') ?? 0);

        $finalAmount = $totalKotor - $discount;
        $dueAmount = $finalAmount - $paid; // Selisih sisa kurang bon pelanggan

        $set('total_amount', $totalKotor);
        $set('final_amount', $finalAmount);
        $set('due_amount', $dueAmount < 0 ? 0 : $dueAmount);
    }
}
