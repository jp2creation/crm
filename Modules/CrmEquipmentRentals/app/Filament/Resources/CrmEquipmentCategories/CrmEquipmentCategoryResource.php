<?php

namespace Modules\CrmEquipmentRentals\Filament\Resources\CrmEquipmentCategories;

use App\Filament\Concerns\AuthorizesResourceWithPolicy;
use App\Models\CrmEquipmentCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\CrmEquipmentRentals\Filament\Resources\CrmEquipmentCategories\Pages\ManageCrmEquipmentCategories;
use UnitEnum;

class CrmEquipmentCategoryResource extends Resource
{
    use AuthorizesResourceWithPolicy;

    protected static ?string $model = CrmEquipmentCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Location materiel';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $modelLabel = 'categorie materiel';

    protected static ?string $pluralModelLabel = 'categories materiel';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(120),
                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(140)
                    ->helperText('Laisse vide pour le generer automatiquement.'),
                TextInput::make('sort_order')
                    ->label('Ordre')
                    ->numeric()
                    ->default(100)
                    ->required(),
                Toggle::make('active')
                    ->label('Categorie active')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Nom'),
                TextEntry::make('slug')->label('Slug'),
                TextEntry::make('sort_order')->label('Ordre'),
                IconEntry::make('active')->label('Actif')->boolean(),
                TextEntry::make('equipment_items_count')->label('Materiels')->counts('equipmentItems'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('equipment_items_count')
                    ->label('Materiels')
                    ->counts('equipmentItems')
                    ->sortable(),
                IconColumn::make('active')
                    ->label('Actif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Actif')
                    ->trueLabel('Actives')
                    ->falseLabel('Masquees'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ManageCrmEquipmentCategories::route('/'),
        ];
    }
}
