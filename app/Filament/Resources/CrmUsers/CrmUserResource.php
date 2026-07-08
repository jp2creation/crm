<?php

namespace App\Filament\Resources\CrmUsers;

use App\Filament\Resources\CrmUsers\Pages\ManageCrmUsers;
use App\Models\CrmUser;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use UnitEnum;

class CrmUserResource extends Resource
{
    protected static ?string $model = CrmUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Administration CRM';

    protected static ?string $navigationLabel = 'Utilisateurs CRM';

    protected static ?string $modelLabel = 'utilisateur CRM';

    protected static ?string $pluralModelLabel = 'utilisateurs CRM';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compte')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(160),
                        Select::make('user_id')
                            ->label('Compte Laravel')
                            ->relationship('account', 'email')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->unique(ignoreRecord: true),
                        Select::make('role')
                            ->label('Profil')
                            ->options(CrmUser::roleOptions())
                            ->required()
                            ->default('user'),
                        Toggle::make('active')
                            ->label('Compte actif')
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make('Acces CRM')
                    ->schema([
                        CheckboxList::make('sites')
                            ->label('Sites autorises')
                            ->relationship('sites', 'name')
                            ->columns(2)
                            ->bulkToggleable(),
                        CheckboxList::make('modules')
                            ->label('Modules autorises')
                            ->relationship('modules', 'name')
                            ->columns(2)
                            ->bulkToggleable(),
                        CheckboxList::make('permissions')
                            ->label('Permissions')
                            ->relationship('permissions', 'label')
                            ->columns(2)
                            ->bulkToggleable(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Nom'),
                TextEntry::make('account.email')->label('Compte Laravel')->placeholder('Non rattache'),
                TextEntry::make('role')->label('Profil')->badge(),
                IconEntry::make('active')->label('Actif')->boolean(),
                TextEntry::make('sites.name')->label('Sites')->badge(),
                TextEntry::make('modules.name')->label('Modules')->badge(),
                TextEntry::make('permissions.label')->label('Permissions')->badge()->columnSpanFull(),
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
                TextColumn::make('role')
                    ->label('Profil')
                    ->badge()
                    ->sortable(),
                TextColumn::make('account.email')
                    ->label('Compte Laravel')
                    ->placeholder('Non rattache')
                    ->searchable(),
                IconColumn::make('active')
                    ->label('Actif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sites_count')
                    ->label('Sites')
                    ->counts('sites')
                    ->sortable(),
                TextColumn::make('modules_count')
                    ->label('Modules')
                    ->counts('modules')
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->label('Droits')
                    ->counts('permissions')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Profil')
                    ->options(CrmUser::roleOptions()),
                TernaryFilter::make('active')
                    ->label('Actif')
                    ->trueLabel('Actifs')
                    ->falseLabel('Masques'),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('createLaravelAccount')
                    ->label('Compte Laravel')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->color('primary')
                    ->visible(fn (CrmUser $record): bool => blank($record->user_id))
                    ->modalHeading('Creer un compte Laravel')
                    ->modalDescription(fn (CrmUser $record): string => "Utilisateur CRM : {$record->name}")
                    ->modalSubmitActionLabel('Creer et rattacher')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->default(fn (CrmUser $record): string => $record->name)
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email')
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Mot de passe initial')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(12)
                            ->maxLength(255),
                        TextInput::make('password_confirmation')
                            ->label('Confirmation')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('password')
                            ->maxLength(255),
                    ])
                    ->action(function (CrmUser $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $record->refresh();

                            if (filled($record->user_id)) {
                                return;
                            }

                            $account = User::query()->create([
                                'name' => $data['name'],
                                'email' => $data['email'],
                                'password' => $data['password'],
                            ]);

                            $role = Role::firstOrCreate([
                                'name' => 'user',
                                'guard_name' => 'web',
                            ]);

                            $account->assignRole($role);

                            $record->forceFill([
                                'user_id' => $account->id,
                                'email' => $record->email ?: $account->email,
                            ])->save();
                        });
                    })
                    ->successNotificationTitle('Compte Laravel cree et rattache'),
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
            'index' => ManageCrmUsers::route('/'),
        ];
    }
}
