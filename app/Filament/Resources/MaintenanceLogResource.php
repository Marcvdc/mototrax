<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceLogResource\Pages;
use App\Models\MaintenanceLog;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class MaintenanceLogResource extends Resource
{
    protected static ?string $model = MaintenanceLog::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('bike_id')
                    ->relationship('bike', 'brand')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->brand} {$record->model}")
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options(MaintenanceLog::getMaintenanceTypes())
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('km_at_maintenance')
                    ->label('KM at Maintenance')
                    ->required()
                    ->numeric(),
                TextInput::make('cost')
                    ->numeric()
                    ->prefix('€')
                    ->step(0.01),
                DatePicker::make('date')
                    ->required()
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bike.brand')
                    ->label('Bike')
                    ->formatStateUsing(fn ($record) => "{$record->bike->brand} {$record->bike->model}")
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn ($state) => MaintenanceLog::getMaintenanceTypes()[$state] ?? $state),
                Tables\Columns\TextColumn::make('km_at_maintenance')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state) . ' km'),
                Tables\Columns\TextColumn::make('cost')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(MaintenanceLog::getMaintenanceTypes()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaintenanceLogs::route('/'),
            'create' => Pages\CreateMaintenanceLog::route('/create'),
            'edit' => Pages\EditMaintenanceLog::route('/{record}/edit'),
        ];
    }
}
