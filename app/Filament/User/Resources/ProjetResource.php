<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\ProjetResource\Pages;
use Illuminate\Support\Facades\Auth;
use App\Models\Projet;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class ProjetResource extends Resource
{
    protected static ?string $model = Projet::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Projets';
    protected static ?string $modelLabel = 'projet';
    protected static ?string $pluralModelLabel = 'projets';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du projet')
                    ->description('Détails principaux du projet')
                    ->schema([
                        Select::make('id_client')
                            ->label('Client')
                            ->relationship('client', 'designation')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Sélectionner un client')
                            ->createOptionForm([
                                TextInput::make('designation')
                                    ->label('Nom du client')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('telephone')
                                    ->label('Téléphone')
                                    ->tel()
                                    ->maxLength(20),
                            ])
                            ->columnSpanFull(),

                        TextInput::make('designation')
                            ->label('Nom du projet')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Création site web, Application mobile...')
                            ->columnSpanFull(),

                        Select::make('statut')
                            ->label('Statut')
                            ->options(Projet::getStatutsLabels())
                            ->default('prospect')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.designation')
                    ->label('Client')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->icon('heroicon-m-building-office-2')
                    ->description(fn (Projet $record): string => 
                        $record->client?->email ?? ''
                    ),

                TextColumn::make('designation')
                    ->label('Nom du projet')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-folder')
                    ->limit(50),

                BadgeColumn::make('statut')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => 
                        Projet::getStatutsLabels()[$state] ?? $state
                    )
                    ->colors([
                        'info' => 'prospect',
                        'warning' => 'devis_envoye',
                        'success' => ['devis_accepte', 'termine'],
                        'primary' => 'demarre',
                        'danger' => 'annule',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date de création')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->since()
                    ->description(fn (Projet $record): string => 
                        $record->created_at->format('d/m/Y')
                    ),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('id_client')
                    ->label('Client')
                    ->relationship('client', 'designation')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer sélectionnés'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Rechercher par nom de projet...')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Détails du projet')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('designation')
                                    ->label('Nom du projet')
                                    ->icon('heroicon-m-folder')
                                    ->weight('bold')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('statut')
                                    ->label('Statut')
                                    ->formatStateUsing(fn (string $state): string => 
                                        Projet::getStatutsLabels()[$state] ?? $state
                                    )
                                    ->badge()
                                    ->color(fn (string $state): string => 
                                        Projet::getStatutsColors()[$state] ?? 'gray'
                                    ),

                                Infolists\Components\TextEntry::make('client.designation')
                                    ->label('Client')
                                    ->icon('heroicon-m-building-office-2')
                                    ->url(fn (Projet $record): string => 
                                        route('filament.user.resources.clients.view', $record->client)
                                    ),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Date de création')
                                    ->getStateUsing(fn ($record) => $record->created_at_french)
                                    ->icon('heroicon-m-calendar'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Informations client')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('client.email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('client.telephone')
                                    ->label('Téléphone')
                                    ->icon('heroicon-m-phone')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('client.adresse.ville')
                                    ->label('Ville')
                                    ->icon('heroicon-m-map-pin'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Informations système')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Créé le')
                                    ->getStateUsing(fn ($record) => $record->created_at_french)
                                    ->icon('heroicon-m-plus-circle'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Modifié le')
                                    ->dateTime('d/m/Y à H:i')
                                    ->since()
                                    ->icon('heroicon-m-pencil-square'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('client', function (Builder $query) {
                $query->where('id_utilisateur', Auth::id());
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjets::route('/'),
            'create' => Pages\CreateProjet::route('/create'),
            'view' => Pages\ViewProjet::route('/{record}'),
            'edit' => Pages\EditProjet::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['designation', 'client.designation'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('client', function (Builder $query) {
            $query->where('id_utilisateur', Auth::id());
        })->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getNavigationBadge();
        if ($count > 10) {
            return 'warning';
        }
        if ($count > 0) {
            return 'primary';
        }
        return 'gray';
    }
}