<?php

namespace Modules\CrmStats\Filament\Resources\CachedBillingStats;

use App\Filament\Concerns\AuthorizesResourceWithPolicy;
use App\Models\CachedBillingStat;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\CrmStats\Filament\Resources\CachedBillingStats\Pages\ManageCachedBillingStats;
use UnitEnum;

class CachedBillingStatResource extends Resource
{
    use AuthorizesResourceWithPolicy;

    protected static ?string $model = CachedBillingStat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Stats';

    protected static ?string $navigationLabel = 'Agregats';

    protected static ?string $modelLabel = 'agregat commercial';

    protected static ?string $pluralModelLabel = 'agregats commerciaux';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('date')->date('d/m/Y'),
                TextEntry::make('client_name')->label('Client'),
                TextEntry::make('product_family')->label('Famille'),
                TextEntry::make('total_amount')->label('CA')->money('EUR'),
                TextEntry::make('invoice_count')->label('Factures'),
                TextEntry::make('quantity')->label('Quantite'),
                TextEntry::make('client_status')->label('Statut')->badge(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('site.name')
                    ->label('Site')
                    ->placeholder('Global')
                    ->sortable(),
                TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_family')
                    ->label('Famille')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('CA')
                    ->money('EUR')
                    ->sortable()
                    ->summarize(Sum::make()->money('EUR')),
                TextColumn::make('invoice_count')
                    ->label('Factures')
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()),
                TextColumn::make('client_status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Synchro')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('client_status')
                    ->label('Statut client')
                    ->options([
                        'active' => 'Actif',
                        'new' => 'Nouveau',
                        'lost' => 'Perdu',
                    ]),
                Filter::make('date')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('date', '<=', $date));
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCachedBillingStats::route('/'),
        ];
    }
}
