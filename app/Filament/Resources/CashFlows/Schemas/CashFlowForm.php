<?php

namespace App\Filament\Resources\CashFlows\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CashFlowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('transaction_date')
                    ->label('Tanggal Pengeluaran')
                    ->required()
                    ->default(now()),
                
                Select::make('description')
                    ->label('Jenis Keperluan')
                    ->options([
                        'Listrik & Air' => 'Bayar Listrik & Air',
                        'Perlengkapan Toko (Kresek/Plastik)' => 'Beli Perlengkapan (Kresek, Plastik, dll)',
                        'Operasional Lainnya' => 'Operasional Lainnya (Bohlam, Sapu, dll)',
                    ])
                    ->required()
                    ->searchable()
                    // RECOVERY: Pakai ini di Filament 5 buat popup form tambah data baru
                    ->createOptionForm([
                        TextInput::make('new_description')
                            ->label('Keperluan Baru')
                            ->required()
                    ])
                    // Proses data yang diketik kasir agar masuk ke piliham options
                    ->createOptionUsing(function (array $data) {
                        return $data['new_description'];
                    }),

                TextInput::make('amount')
                    ->label('Nominal Pengeluaran')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Textarea::make('notes')
                    ->label('Keterangan Tambahan / Catatan')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
