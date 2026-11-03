<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'User';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'User';

    protected static ?int $navigationSort = 90;

    public static function canViewAny(): bool
    {
        return (int) Auth::user()?->level === 0;
    }

    public static function canCreate(): bool
    {
        return (int) Auth::user()?->level === 0;
    }

    public static function canEdit($record): bool
    {
        return (int) Auth::user()?->level === 0;
    }

    public static function canDelete($record): bool
    {
        return (int) Auth::user()?->level === 0
            && $record->getKey() !== Auth::id();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(255),

            TextInput::make('username')
                ->label('Username')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->minLength(8),

            Select::make('level')
                ->label('Level')
                ->options([
                    0 => 'Admin',
                    1 => 'Operator',
                ])
                ->default(1)
                ->required()
                ->native(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('username')->label('Username')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => (int) $state === 0 ? 'Admin' : 'Operator')
                    ->color(fn ($state): string => (int) $state === 0 ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name')
            ->paginated([15])
            ->defaultPaginationPageOption(15);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
