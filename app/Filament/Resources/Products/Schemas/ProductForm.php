<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')
                    ->components([
                        TextInput::make('name')
                            ->label('Nama Produk/Ikan')
                            ->required()
                            ->maxLength(255),
                        Select::make('category_id')
                            ->label('Jenis / Kategori Barang')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama Kategori Baru')
                                    ->required(),
                            ]),
                        TextInput::make('sku')
                            ->label('Kode/SKU (Opsional)')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Harga & Kontrol Stok')
                    ->components([
                        TextInput::make('price')
                            ->label('Harga Jual Master')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->default(0),
                        TextInput::make('min_stock')
                            ->label('Batas Stok Minimum')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->helperText('Sistem bakal ngasih alarm merah kalau stok sisa dari seluruh batch menyentuh angka ini.'),
                        Toggle::make('is_active')
                            ->label('Status Jual')
                            ->default(true)
                            ->helperText('Matikan jika produk sedang kosong atau ikan sakit.'),
                    ])->columns(3),
            ]);
    }
}
