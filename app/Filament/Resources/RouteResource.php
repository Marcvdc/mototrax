<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouteResource\Pages;
use App\Models\Route;
use App\Services\RouteService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\View as ViewComponent;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

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
                    ->disk(RouteService::DISK)
                    ->directory(RouteService::DIRECTORY)
                    ->maxSize(10240)
                    ->required(),
                Toggle::make('is_public')
                    ->label('Publiek zichtbaar')
                    ->helperText('Publieke routes zijn vindbaar voor andere riders.')
                    ->default(false),
                CheckboxList::make('tags')
                    ->options(Route::getCommonTags())
                    ->columns(3),
                Select::make('difficulty')
                    ->options(Route::getDifficultyLevels()),
                TextInput::make('distance')
                    ->numeric()
                    ->step(0.001)
                    ->suffix('km')
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Wordt automatisch berekend uit het GPX bestand.'),
                TextInput::make('estimated_time')
                    ->label('Estimated Time (minutes)')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Wordt automatisch berekend uit het GPX bestand.'),
                TextInput::make('waypoint_count')
                    ->label('Waypoints')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
                ViewComponent::make('filament.route.map-preview')
                    ->visible(fn (?string $operation): bool => $operation !== 'create')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')->label('Eigenaar'),
                TextEntry::make('name')->label('Naam'),
                TextEntry::make('description')
                    ->label('Beschrijving')
                    ->placeholder('—')
                    ->columnSpanFull(),
                IconEntry::make('is_public')
                    ->label('Publiek zichtbaar')
                    ->boolean(),
                TextEntry::make('difficulty')
                    ->label('Niveau')
                    ->formatStateUsing(fn ($state) => Route::getDifficultyLevels()[$state] ?? '—'),
                TextEntry::make('distance')
                    ->label('Afstand')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).' km' : '—'),
                TextEntry::make('estimated_time')
                    ->label('Geschatte tijd')
                    ->formatStateUsing(fn ($record) => $record->estimated_time ? $record->formatted_time : '—'),
                TextEntry::make('waypoint_count')
                    ->label('Waypoints')
                    ->placeholder('—'),
                TextEntry::make('tags')
                    ->label('Tags')
                    ->formatStateUsing(fn ($state) => collect((array) $state)
                        ->map(fn ($t) => Route::getCommonTags()[$t] ?? $t)
                        ->join(', ') ?: '—')
                    ->columnSpanFull(),
                ViewEntry::make('map')
                    ->label('')
                    ->view('filament.route.map-preview')
                    ->columnSpanFull(),
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
                Tables\Columns\IconColumn::make('is_public')
                    ->label('Publiek')
                    ->boolean(),
                Tables\Columns\TextColumn::make('distance')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).' km' : 'N/A'),
                Tables\Columns\TextColumn::make('estimated_time')
                    ->label('Time')
                    ->formatStateUsing(fn ($record) => $record->formatted_time),
                Tables\Columns\TextColumn::make('difficulty')
                    ->formatStateUsing(fn ($state) => Route::getDifficultyLevels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('waypoint_count')
                    ->label('Waypoints')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('difficulty')
                    ->options(Route::getDifficultyLevels()),
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Publiek'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('download_gpx')
                    ->label('Download GPX')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Route $record): string => route('api.routes.gpx', ['route' => $record->id]))
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
            'view' => Pages\ViewRoute::route('/{record}'),
            'edit' => Pages\EditRoute::route('/{record}/edit'),
        ];
    }
}
