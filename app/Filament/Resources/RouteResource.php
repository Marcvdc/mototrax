<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouteResource\Pages;
use App\Models\Route;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMapPin;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('gpx_file')
                    ->label('GPX File')
                    ->acceptedFileTypes(['application/gpx+xml', 'application/xml', 'text/xml'])
                    ->directory('gpx')
                    ->maxSize(10240)
                    ->required(),
                CheckboxList::make('tags')
                    ->options(Route::getCommonTags())
                    ->columns(3),
                TextInput::make('distance')
                    ->numeric()
                    ->step(0.1)
                    ->suffix('km'),
                TextInput::make('estimated_time')
                    ->label('Estimated Time (minutes)')
                    ->numeric(),
                Select::make('difficulty')
                    ->options(Route::getDifficultyLevels()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('distance')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' km' : 'N/A'),
                Tables\Columns\TextColumn::make('estimated_time')
                    ->label('Time')
                    ->formatStateUsing(fn ($record) => $record->formatted_time),
                Tables\Columns\TextColumn::make('difficulty')
                    ->formatStateUsing(fn ($state) => Route::getDifficultyLevels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('difficulty')
                    ->options(Route::getDifficultyLevels()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('download_gpx')
                    ->label('Download GPX')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Route $record): string => $record->gpx_url)
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListRoutes::route('/'),
            'create' => Pages\CreateRoute::route('/create'),
            'edit' => Pages\EditRoute::route('/{record}/edit'),
        ];
    }
}
