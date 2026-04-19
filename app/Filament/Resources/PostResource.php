<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use BackedEnum;
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

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->options(Post::getPostTypes())
                    ->required()
                    ->live(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull()
                    ->rows(3),
                Select::make('route_id')
                    ->label('Route (for Route Share posts)')
                    ->relationship('route', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('type') === 'route_share'),
                Select::make('maintenance_log_id')
                    ->label('Maintenance Log (for Maintenance posts)')
                    ->relationship('maintenanceLog', 'title')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('type') === 'maintenance'),
                TextInput::make('likes_count')
                    ->numeric()
                    ->default(0),
                TextInput::make('comments_count')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn ($state) => Post::getPostTypes()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'route_share' => 'info',
                        'maintenance' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('content')
                    ->limit(100)
                    ->searchable(),
                Tables\Columns\TextColumn::make('route.name')
                    ->label('Related Route')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('maintenanceLog.title')
                    ->label('Related Maintenance')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('likes_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('comments_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(Post::getPostTypes()),
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
