<?php

namespace App\Filament\Resources\CashFlows;

use App\Filament\Resources\CashFlows\Pages\ListCashFlows;
use App\Filament\Resources\CashFlows\Schemas\CashFlowForm;
use App\Filament\Resources\CashFlows\Tables\CashFlowsTable;
use App\Models\CashFlow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashFlowResource extends Resource
{
    protected static ?string $model = CashFlow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'Expense')
            ->where('category', 'Operational');
    }
    
    public static function form(Schema $schema): Schema
    {
        return CashFlowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashFlowsTable::configure($table);
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
            'index' => ListCashFlows::route('/'),
        ];
    }
}
