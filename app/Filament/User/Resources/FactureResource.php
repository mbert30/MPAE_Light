<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\FactureResource\Pages;
use App\Models\Facture;
use App\Models\Devis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class FactureResource extends Resource
{
    protected static ?string $model = Facture::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    
    protected static ?string $navigationLabel = 'Factures';
    
    protected static ?string $modelLabel = 'facture';
    
    protected static ?string $pluralModelLabel = 'factures';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la facture')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('id_devis')
                                    ->label('Devis')
                                    ->relationship(
                                        name: 'devis',
                                        titleAttribute: 'numero_devis',
                                        modifyQueryUsing: fn (Builder $query) => $query
                                            ->whereHas('projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                                            ->where('statut', 'accepte')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (Devis $record): string => 
                                        "Devis #{$record->numero_devis} - {$record->projet->designation}"
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled() // Toujours désactivé car pré-rempli depuis le devis
                                    ->dehydrated() // S'assurer que la valeur est incluse même si disabled
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        if ($state) {
                                            $devis = Devis::find($state);
                                            if ($devis) {
                                                $set('taux_tva', $devis->taux_tva);
                                            }
                                        }
                                    })
                                    ->rules([
                                        function ($livewire, $record) {
                                            return function (string $attribute, $value, \Closure $fail) use ($livewire, $record) {
                                                // En édition, vérifier que le devis n'a pas changé
                                                if ($record && $record->id_devis != $value) {
                                                    $fail('Le devis associé à une facture ne peut pas être modifié.');
                                                }
                                            };
                                        }
                                    ]),

                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('numero_facture')
                                            ->label('Numéro de facture')
                                            ->required()
                                            ->numeric()
                                            ->default(fn () => Facture::getNextNumeroFacture(Auth::id()))
                                            ->disabled(fn ($record) => $record && $record->etat_facture !== 'brouillon')
                                            ->suffixAction(
                                                Action::make('generateNumber')
                                                    ->label('Générer')
                                                    ->icon('heroicon-m-arrow-path')
                                                    ->visible(fn ($record) => !$record || $record->etat_facture === 'brouillon')
                                                    ->action(function (Forms\Set $set) {
                                                        $set('numero_facture', Facture::getNextNumeroFacture(Auth::id()));
                                                        
                                                        Notification::make()
                                                            ->title('Numéro généré')
                                                            ->success()
                                                            ->send();
                                                    })
                                            )
                                            ->rules([
                                                function ($livewire) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                                        $currentFactureId = null;
                                                        
                                                        if (isset($livewire->record) && $livewire->record) {
                                                            $currentFactureId = $livewire->record->getKey();
                                                        }
                                                        
                                                        if (!$currentFactureId) {
                                                            $url = request()->url();
                                                            if (preg_match('/\/admin\/factures\/(\d+)\/edit/', $url, $matches)) {
                                                                $currentFactureId = (int) $matches[1];
                                                            }
                                                        }
                                                        
                                                        if (!Facture::isUniqueNumeroFacture((int) $value, Auth::id(), $currentFactureId)) {
                                                            $fail('Ce numéro de facture est déjà utilisé.');
                                                        }
                                                    };
                                                }
                                            ]),

                                        Forms\Components\Select::make('etat_facture')
                                            ->label('État')
                                            ->options([
                                                'brouillon' => 'Brouillon',
                                                'envoyee' => 'Envoyée',
                                            ])
                                            ->default('brouillon')
                                            ->required()
                                            ->disabled(fn ($record) => $record && $record->etat_facture === 'payee')
                                            ->rules([
                                                function (Forms\Get $get, $livewire) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($get, $livewire) {
                                                        if ($value === 'envoyee') {
                                                            $lignesFacturation = $get('lignesFacturation') ?? [];
                                                            
                                                            $lignesValides = collect($lignesFacturation)->filter(function ($ligne) {
                                                                return !empty($ligne['libelle']) && !empty($ligne['prix_unitaire']) && !empty($ligne['quantite']);
                                                            })->count();
                                                            
                                                            if ($lignesValides === 0) {
                                                                $fail('La facture doit contenir au moins une ligne pour être envoyée.');
                                                            }
                                                        }
                                                    };
                                                }
                                            ]),

                                        Forms\Components\TextInput::make('taux_tva')
                                            ->label('Taux TVA (%)')
                                            ->numeric()
                                            ->step(0.01)
                                            ->suffix('%')
                                            ->disabled()
                                            ->helperText('Hérité du devis'),

                                        Forms\Components\DatePicker::make('date_edition')
                                            ->label('Date d\'édition')
                                            ->required()
                                            ->default(today())
                                            ->disabled(fn ($record) => $record && $record->etat_facture !== 'brouillon'),
                                    ]),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('date_paiement_limite')
                                    ->label('Date limite de paiement')
                                    ->required()
                                    ->default(fn () => today()->addDays(30))
                                    ->minDate(today())
                                    ->disabled(fn ($record) => 
                                        ($record && $record->etat_facture === 'payee') || 
                                        ($record && $record->etat_facture === 'envoyee')
                                    ),

                                Forms\Components\Select::make('type_paiement')
                                    ->label('Type de paiement')
                                    ->options([
                                        'virement' => 'Virement',
                                        'cheque' => 'Chèque',
                                        'especes' => 'Espèces',
                                        'carte' => 'Carte',
                                        'paypal' => 'PayPal',
                                        'autre' => 'Autre',
                                    ])
                                    ->disabled(fn ($record) => 
                                        ($record && $record->etat_facture === 'payee') || 
                                        ($record && $record->etat_facture === 'envoyee')
                                    ),

                                Forms\Components\DatePicker::make('date_paiement_effectif')
                                    ->label('Date de paiement effectif')
                                    ->visible(fn (Forms\Get $get) => $get('etat_facture') === 'payee')
                                    ->required(fn (Forms\Get $get) => $get('etat_facture') === 'payee')
                                    ->maxDate(today())
                                    ->disabled(fn ($record) => $record && $record->etat_facture === 'payee'),
                            ]),

                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn ($record) => 
                                ($record && $record->etat_facture === 'payee') || 
                                ($record && $record->etat_facture === 'envoyee')
                            ),
                    ]),

                Forms\Components\Section::make('Lignes de facturation')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('load_lignes')
                                ->label('Charger les lignes du devis')
                                ->icon('heroicon-m-arrow-down-tray')
                                ->color('primary')
                                ->action(function ($livewire, Forms\Set $set, Forms\Get $get) {
                                    $devis = null;
                                    
                                    // Récupérer le devis selon le contexte
                                    if ($livewire instanceof \App\Filament\User\Resources\FactureResource\Pages\CreateFacture && $livewire->devis) {
                                        // Page de création : utiliser $livewire->devis
                                        $devis = $livewire->devis;
                                    } elseif ($livewire instanceof \App\Filament\User\Resources\FactureResource\Pages\EditFacture && $livewire->record) {
                                        // Page d'édition : récupérer le devis via la facture
                                        $devis = $livewire->record->devis;
                                    } elseif ($get('id_devis')) {
                                        // Fallback : récupérer via l'ID du devis dans le formulaire
                                        $devis = \App\Models\Devis::find($get('id_devis'));
                                    }
                                    
                                    if ($devis && $devis->lignesDevis->isNotEmpty()) {
                                        $lignes = $devis->lignesDevis->map(function ($ligne) {
                                            return [
                                                'libelle' => $ligne->libelle,
                                                'prix_unitaire' => $ligne->prix_unitaire,
                                                'quantite' => $ligne->quantite,
                                                'ordre' => $ligne->ordre,
                                            ];
                                        })->toArray();
                                        
                                        $set('lignesFacturation', $lignes);
                                        
                                        Notification::make()
                                            ->title('Lignes chargées')
                                            ->body(count($lignes) . ' ligne(s) du devis ont été chargées.')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Aucune ligne à charger')
                                            ->body('Le devis associé ne contient aucune ligne.')
                                            ->warning()
                                            ->send();
                                    }
                                })
                                ->visible(function ($livewire, $get) {
                                    $devis = null;
                                    
                                    // Déterminer si le bouton doit être visible
                                    if ($livewire instanceof \App\Filament\User\Resources\FactureResource\Pages\CreateFacture && $livewire->devis) {
                                        $devis = $livewire->devis;
                                    } elseif ($livewire instanceof \App\Filament\User\Resources\FactureResource\Pages\EditFacture && $livewire->record) {
                                        $devis = $livewire->record->devis;
                                        // En édition, ne montrer le bouton que si la facture est en brouillon
                                        if ($livewire->record->etat_facture !== 'brouillon') {
                                            return false;
                                        }
                                    } elseif ($get('id_devis')) {
                                        $devis = \App\Models\Devis::find($get('id_devis'));
                                    }
                                    
                                    return $devis && $devis->lignesDevis->isNotEmpty();
                                })
                                ->requiresConfirmation() // Ajouter une confirmation pour éviter les erreurs
                                ->modalHeading('Charger les lignes du devis')
                                ->modalDescription('Attention : Cette action remplacera toutes les lignes actuelles par celles du devis. Voulez-vous continuer ?')
                                ->modalSubmitActionLabel('Charger les lignes'),
                        ])
                            ->columnSpanFull(),
                            Forms\Components\Repeater::make('lignesFacturation')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\TextInput::make('libelle')
                                                ->label('Libellé')
                                                ->required()
                                                ->columnSpan(2)
                                                ->readOnly(fn ($record) => 
                                                    $record && 
                                                    ($record->facture->etat_facture === 'envoyee' || $record->facture->etat_facture === 'payee')
                                                )
                                                ->extraInputAttributes(fn ($record) => 
                                                    $record && 
                                                    ($record->facture->etat_facture === 'envoyee' || $record->facture->etat_facture === 'payee')
                                                    ? ['style' => 'cursor: not-allowed;'] : []
                                                ),

                                            Forms\Components\TextInput::make('prix_unitaire')
                                                ->label('Prix unitaire (€)')
                                                ->required()
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('€')
                                                ->readOnly(fn ($record) => 
                                                    $record && 
                                                    ($record->facture->etat_facture === 'envoyee' || $record->facture->etat_facture === 'payee')
                                                )
                                                ->extraInputAttributes(fn ($record) => 
                                                    $record && 
                                                    ($record->facture->etat_facture === 'envoyee' || $record->facture->etat_facture === 'payee')
                                                    ? ['style' => 'cursor: not-allowed;'] : []
                                                ),

                                            Forms\Components\TextInput::make('quantite')
                                                ->label('Quantité')
                                                ->required()
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(0.01)
                                                ->readOnly(fn ($record) => 
                                                    $record && 
                                                    ($record->facture->etat_facture === 'envoyee' || $record->facture->etat_facture === 'payee')
                                                )
                                                ->extraInputAttributes(fn ($record) => 
                                                    $record && 
                                                    ($record->facture->etat_facture === 'envoyee' || $record->facture->etat_facture === 'payee')
                                                    ? ['style' => 'cursor: not-allowed;'] : []
                                                ),

                                            Forms\Components\Hidden::make('ordre'),
                                        ]),
                                ])
                                ->columns(1)
                                ->defaultItems(0)
                                ->reorderable('ordre')
                                ->orderColumn('ordre')
                                ->itemLabel(fn (array $state): ?string => $state['libelle'] ?? 'Nouvelle ligne')
                                ->disabled(fn ($record) => $record && $record->etat_facture === 'payee')
                                ->deletable(fn ($record) => !$record || ($record->etat_facture !== 'payee' && $record->etat_facture !== 'envoyee'))
                                ->addable(fn ($record) => !$record || ($record->etat_facture !== 'payee' && $record->etat_facture !== 'envoyee'))
                                ->reorderable(fn ($record) => !$record || ($record->etat_facture !== 'payee' && $record->etat_facture !== 'envoyee')),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_facture')
                    ->label('N° Facture')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('devis.projet.designation')
                    ->label('Projet')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('devis.projet.client.designation')
                    ->label('Client')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('etat_facture')
                    ->label('État')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'brouillon' => 'gray',
                        'envoyee' => 'warning',
                        'payee' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'brouillon' => 'Brouillon',
                        'envoyee' => 'Envoyée',
                        'payee' => 'Payée',
                    }),

                Tables\Columns\TextColumn::make('date_paiement_limite')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Facture $record): string => 
                        $record->est_en_retard ? 'danger' : 'gray'
                    )
                    ->icon(fn (Facture $record): ?string => 
                        $record->est_en_retard ? 'heroicon-o-exclamation-triangle' : null
                    )
                    ->formatStateUsing(function (Facture $record, $state): string {
                        $date = $state instanceof \Carbon\Carbon ? $state->format('d/m/Y') : $state;
                        return $record->est_en_retard ? $date . ' ⚠️' : $date;
                    }),

                Tables\Columns\TextColumn::make('montant_total_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('montant_total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifiée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('etat_facture')
                    ->label('État')
                    ->options([
                        'brouillon' => 'Brouillon',
                        'envoyee' => 'Envoyée',
                        'payee' => 'Payée',
                    ]),

                Tables\Filters\Filter::make('en_retard')
                    ->label('En retard')
                    ->query(fn (Builder $query): Builder => $query->enRetard())
                    ->indicateUsing(fn (array $data): ?string => 
                        $data['isActive'] ?? false ? 'Factures en retard' : null
                    ),

                Tables\Filters\Filter::make('ce_mois')
                    ->label('Ce mois')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year)
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Facture $record): bool => $record->canBeModified()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Facture $record): bool => $record->canBeDeleted()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!$record->canBeDeleted()) {
                                    Notification::make()
                                        ->title('Suppression impossible')
                                        ->body("La facture #{$record->numero_facture} ne peut pas être supprimée.")
                                        ->danger()
                                        ->send();
                                    return;
                                }
                            }
                            $records->each->delete();
                        }),
                ]),
            ])
            ->defaultSort('numero_facture', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('devis.projet.client', fn ($query) => $query->where('id_utilisateur', Auth::id()));
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
            'index' => Pages\ListFactures::route('/'),
            'create' => Pages\CreateFacture::route('/create'),
            'view' => Pages\ViewFacture::route('/{record}'),
            'edit' => Pages\EditFacture::route('/{record}/edit'),
        ];
    }
}