<?php

namespace App\Filament\Resources\CrmEquipmentRentals;

use App\Filament\Resources\CrmEquipmentRentals\Pages\ManageCrmEquipmentRentals;
use App\Models\CrmEquipmentRental;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CrmEquipmentRentalResource extends Resource
{
    protected static ?string $model = CrmEquipmentRental::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Location materiel';

    protected static ?string $navigationLabel = 'Locations';

    protected static ?string $modelLabel = 'location materiel';

    protected static ?string $pluralModelLabel = 'locations materiel';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('equipment_item_id')
                    ->label('Materiel')
                    ->relationship('equipmentItem', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('period_type')
                    ->label('Duree')
                    ->options(CrmEquipmentRental::periodTypeOptions())
                    ->default('half_day')
                    ->required(),
                Select::make('slot')
                    ->label('Creneau')
                    ->options(CrmEquipmentRental::slotOptions())
                    ->default('morning')
                    ->required(),
                Select::make('status')
                    ->label('Statut')
                    ->options(CrmEquipmentRental::statusOptions())
                    ->default(CrmEquipmentRental::STATUS_RESERVED)
                    ->required(),
                TextInput::make('title')
                    ->label('Client / chantier')
                    ->maxLength(190),
                TextInput::make('contact_phone')
                    ->label('Telephone')
                    ->maxLength(40),
                DateTimePicker::make('start_at')
                    ->label('Debut')
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('end_at')
                    ->label('Fin')
                    ->seconds(false)
                    ->required(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('equipmentItem.name')->label('Materiel'),
                TextEntry::make('site.name')->label('Site'),
                TextEntry::make('user_name')->label('Utilisateur'),
                TextEntry::make('title')->label('Client / chantier')->placeholder('-'),
                TextEntry::make('period_type')->label('Duree')->formatStateUsing(fn (?string $state): string => CrmEquipmentRental::periodTypeOptions()[$state] ?? (string) $state),
                TextEntry::make('slot')->label('Creneau')->formatStateUsing(fn (?string $state): string => CrmEquipmentRental::slotOptions()[$state] ?? (string) $state),
                TextEntry::make('status')->label('Statut')->badge()->formatStateUsing(fn (?string $state): string => CrmEquipmentRental::statusOptions()[$state] ?? (string) $state),
                TextEntry::make('start_at')->label('Debut')->dateTime('d/m/Y H:i'),
                TextEntry::make('end_at')->label('Fin')->dateTime('d/m/Y H:i'),
                TextEntry::make('notes')->label('Notes')->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('equipmentItem.name')
                    ->label('Materiel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.name')
                    ->label('Site')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user_name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Client / chantier')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('period_type')
                    ->label('Duree')
                    ->formatStateUsing(fn (?string $state): string => CrmEquipmentRental::periodTypeOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (?string $state): string => CrmEquipmentRental::statusOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->sortable(),
                TextColumn::make('start_at')
                    ->label('Debut')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('site')
                    ->label('Site')
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('equipment_item')
                    ->label('Materiel')
                    ->relationship('equipmentItem', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(CrmEquipmentRental::statusOptions()),
                SelectFilter::make('period_type')
                    ->label('Duree')
                    ->options(CrmEquipmentRental::periodTypeOptions()),
                SelectFilter::make('upcoming')
                    ->label('Periode')
                    ->options(['upcoming' => 'A venir'])
                    ->query(fn (Builder $query, array $data): Builder => ($data['value'] ?? null) === 'upcoming'
                        ? $query->where('end_at', '>=', now())
                        : $query),
            ])
            ->defaultSort('start_at', 'desc')
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
            'index' => ManageCrmEquipmentRentals::route('/'),
        ];
    }
}
