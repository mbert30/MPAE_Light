<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\AdresseResource\Pages;
use App\Filament\User\Resources\AdresseResource\RelationManagers;
use App\Models\Adresse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdresseResource extends Resource
{
    protected static ?string $model = Adresse::class;
    protected static ?string $navigationGroup = null;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ligne1')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ligne2')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('ligne3')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('ville')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code_postal')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('pays')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ligne1')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ligne2')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ligne3')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ville')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code_postal')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pays')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdresses::route('/'),
            'create' => Pages\CreateAdresse::route('/create'),
            'edit' => Pages\EditAdresse::route('/{record}/edit'),
        ];
    }
}
