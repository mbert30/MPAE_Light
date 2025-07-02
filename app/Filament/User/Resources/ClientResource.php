<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clients';
    protected static ?string $modelLabel = 'Client';
    protected static ?string $pluralModelLabel = 'Clients';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations client')
                    ->schema([
                        Forms\Components\TextInput::make('designation')
                            ->label('Nom du client')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Entreprise SARL'),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('contact@entreprise.com'),
                        
                        Forms\Components\TextInput::make('telephone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('01 23 45 67 89'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Adresse')
                    ->schema([
                        Forms\Components\TextInput::make('adresse.ligne1')
                            ->label('Adresse')
                            ->required()
                            ->placeholder('Numéro et nom de rue'),
                        
                        Forms\Components\TextInput::make('adresse.ligne2')
                            ->label('Complément d\'adresse')
                            ->placeholder('Bâtiment, étage, etc.'),
                        
                        Forms\Components\TextInput::make('adresse.ligne3')
                            ->label('Lieu-dit / Zone')
                            ->placeholder('Zone industrielle, etc.'),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('adresse.code_postal')
                                    ->label('Code postal')
                                    ->required()
                                    ->length(5)
                                    ->numeric()
                                    ->placeholder('75000'),
                                
                                Forms\Components\TextInput::make('adresse.ville')
                                    ->label('Ville')
                                    ->required()
                                    ->placeholder('Paris'),
                                
                                Forms\Components\TextInput::make('adresse.pays')
                                    ->label('Pays')
                                    ->default('France')
                                    ->required(),
                            ])
                    ])
                    ->columns(1)
            ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['adresse']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['designation', 'email', 'telephone'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('designation')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Client $record): string => $record->email ?? '')
                    ->icon('heroicon-m-building-office-2'),

                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage('Email copié!')
                    ->toggleable(),

                TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->sortable()
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Téléphone copié!')
                    ->formatStateUsing(fn (string $state): string => 
                        preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $state)
                    ),

                TextColumn::make('adresse.ville')
                    ->label('Ville')
                    ->sortable()
                    ->icon('heroicon-m-map-pin')
                    ->description(fn (Client $record): string => 
                        $record->adresse ? $record->adresse->code_postal : ''
                    ),

                TextColumn::make('adresse.pays')
                    ->label('Pays')
                    ->searchable(['adresses.pays'])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('projets_count')
                    ->label('Projets')
                    ->counts('projets')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state <= 3 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                // Pas d'actions de header
            ])
            ->filters([
                Tables\Filters\Filter::make('search_custom')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('field')
                                    ->label('Rechercher par')
                                    ->options([
                                        'designation' => 'Nom du client',
                                        'email' => 'Email',
                                        'telephone' => 'Téléphone',
                                        'ville' => 'Ville',
                                    ])
                                    ->default('designation')
                                    ->placeholder('Choisir un champ'),
                                Forms\Components\TextInput::make('value')
                                    ->label('Terme de recherche')
                                    ->placeholder('Tapez votre recherche...'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['field']) || !isset($data['value']) || !$data['value']) {
                            return $query;
                        }

                        return match($data['field']) {
                            'designation' => $query->where('designation', 'like', "%{$data['value']}%"),
                            'email' => $query->where('email', 'like', "%{$data['value']}%"),
                            'telephone' => $query->where('telephone', 'like', "%{$data['value']}%"),
                            'ville' => $query->whereHas('adresse', function ($q) use ($data) {
                                $q->where('ville', 'like', "%{$data['value']}%");
                            }),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!isset($data['field']) || !isset($data['value']) || !$data['value']) {
                            return null;
                        }

                        $fieldLabels = [
                            'designation' => 'Nom du client',
                            'email' => 'Email',
                            'telephone' => 'Téléphone',
                            'ville' => 'Ville',
                        ];

                        return "Recherche: {$fieldLabels[$data['field']]} contient \"{$data['value']}\"";
                    }),

                SelectFilter::make('ville')
                    ->label('Filtrer par ville')
                    ->relationship('adresse', 'ville')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('has_projects')
                    ->label('Avec projets')
                    ->queries(
                        true: fn (Builder $query) => $query->has('projets'),
                        false: fn (Builder $query) => $query->doesntHave('projets'),
                    ),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer le client')
                    ->modalDescription(function (Client $record) {
                        $nombreProjets = $record->projets()->count();
                        
                        if ($nombreProjets > 0) {
                            return "⚠️ Ce client possède {$nombreProjets} projet(s). Vous devez d'abord supprimer tous les projets associés avant de pouvoir supprimer ce client.";
                        }
                        
                        return 'Êtes-vous sûr de vouloir supprimer ce client ? Cette action est irréversible.';
                    })
                    ->modalSubmitActionLabel(function (Client $record) {
                        $nombreProjets = $record->projets()->count();
                        return $nombreProjets > 0 ? 'Impossible' : 'Supprimer';
                    })
                    ->action(function (Client $record) {
                        $nombreProjets = $record->projets()->count();
                        
                        if ($nombreProjets > 0) {
                            Notification::make()
                                ->danger()
                                ->title('Suppression impossible')
                                ->body("Ce client possède {$nombreProjets} projet(s). Supprimez d'abord tous les projets associés.")
                                ->persistent()
                                ->send();
                            return;
                        }
                        
                        $record->delete();
                        
                        Notification::make()
                            ->success()
                            ->title('Client supprimé')
                            ->body('Le client a été supprimé avec succès.')
                            ->send();
                    })
                    ->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer sélection'),
                ]),
            ])
            ->defaultSort('designation')
            ->searchPlaceholder('Rechercher par nom du client...')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateHeading('Aucun client')
            ->emptyStateDescription('Commencez par créer votre premier client.')
            ->emptyStateIcon('heroicon-o-users')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['adresse', 'projets'])
            ->where('id_utilisateur', Auth::id()); // Afficher seulement les clients de l'utilisateur connecté
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}