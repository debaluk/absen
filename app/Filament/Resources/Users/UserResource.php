<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

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
                ->label('Nama Lengkap')
                ->required()
                ->maxLength(255),

            TextInput::make('username')
                ->label('Nama Pengguna')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('email')
                ->label('Alamat Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('password')
                ->label('Kata Sandi')
                ->password()
                ->revealable()
                ->confirmed()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->minLength(8),

            TextInput::make('password_confirmation')
                ->label('Ulang Kata Sandi')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(false),

            Select::make('level')
                ->label('Level Pengguna')
                ->options([
                    0 => 'Administrator',
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
                TextColumn::make('name')->label('Nama Lengkap')->searchable()->sortable(),
                TextColumn::make('username')->label('Nama Pengguna')->searchable()->sortable(),
                TextColumn::make('email')->label('Alamat Email')->searchable(),
                TextColumn::make('level')
                    ->label('Hak Akses')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => (int) $state === 0 ? 'Administrator' : 'Operator')
                    ->color(fn ($state): string => (int) $state === 0 ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah')
                    ->modalHeading('Ubah Pengguna')
                    ->modalSubmitActionLabel('Simpan Perubahan'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus Pengguna')
                    ->modalDescription('Pengguna yang dihapus tidak dapat masuk kembali ke aplikasi.')
                    ->modalSubmitActionLabel('Ya, Hapus Pengguna'),
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
