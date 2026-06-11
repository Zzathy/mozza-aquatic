<?php

namespace App\Filament\Resources\StockEntries;

use App\Filament\Resources\StockEntries\Pages\CreateStockEntry;
use App\Filament\Resources\StockEntries\Pages\EditStockEntry;
use App\Filament\Resources\StockEntries\Pages\ListStockEntries;
use App\Filament\Resources\StockEntries\Schemas\StockEntryForm;
use App\Filament\Resources\StockEntries\Tables\StockEntriesTable;
use App\Models\StockEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockEntryResource extends Resource
{
    protected static ?string $model = StockEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StockEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockEntriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockEntries::route('/'),
            'create' => CreateStockEntry::route('/create'),
            'edit' => EditStockEntry::route('/{record}/edit'),
        ];
    }
}
