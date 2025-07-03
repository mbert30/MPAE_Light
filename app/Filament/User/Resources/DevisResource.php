<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\DevisResource\Pages;
use App\Models\Devis;
use App\Models\Projet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class DevisResource extends Resource
{
    protected static ?string $model = Devis::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Devis';
    
    protected static ?string $modelLabel = 'devis';
    
    protected static ?string $pluralModelLabel = 'devis';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du devis')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('id_projet')
                                    ->label('Projet')
                                    ->relationship(
                                        name: 'projet',
                                        titleAttribute: 'designation',
                                        modifyQueryUsing: fn (Builder $query) => $query->whereHas('client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn ($record) => $record && $record->statut !== 'brouillon')
                                    ->createOptionForm([
                                        Forms\Components\Select::make('id_client')
                                            ->label('Client')
                                            ->relationship(
                                                name: 'client',
                                                titleAttribute: 'designation',
                                                modifyQueryUsing: fn (Builder $query) => $query->where('id_utilisateur', Auth::id())
                                            )
                                            ->required(),
                                        Forms\Components\TextInput::make('designation')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('numero_devis')
                                            ->label('Numéro de devis')
                                            ->required()
                                            ->numeric()
                                            ->default(fn () => Devis::getNextNumeroDevis(Auth::id()))
                                            ->disabled(fn ($record) => $record && $record->statut !== 'brouillon')
                                            ->suffixAction(
                                                Action::make('generateNumber')
                                                    ->label('Générer')
                                                    ->icon('heroicon-m-arrow-path')
                                                    ->visible(fn ($record) => !$record || $record->statut === 'brouillon')
                                                    ->action(function (Forms\Set $set) {
                                                        $set('numero_devis', Devis::getNextNumeroDevis(Auth::id()));
                                                        
                                                        Notification::make()
                                                            ->title('Numéro généré')
                                                            ->success()
                                                            ->send();
                                                    })
                                            )
                                            ->rules([
                                                function ($livewire) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                                        $currentDevisId = null;
                                                        
                                                        if (isset($livewire->record) && $livewire->record) {
                                                            $currentDevisId = $livewire->record->getKey();
                                                        }
                                                        
                                                        if (!$currentDevisId) {
                                                            $url = request()->url();
                                                            if (preg_match('/\/devis\/(\d+)\/edit/', $url, $matches)) {
                                                                $currentDevisId = (int) $matches[1];
                                                            }
                                                        }
                                                        
                                                        if (!Devis::isUniqueNumeroDevis((int) $value, Auth::id(), $currentDevisId)) {
                                                            $fail('Ce numéro de devis est déjà utilisé.');
                                                        }
                                                    };
                                                }
                                            ]),

                                        Forms\Components\Select::make('statut')
                                            ->label('Statut')
                                            ->options([
                                                'brouillon' => 'Brouillon',
                                                'envoye' => 'Envoyé',
                                                'accepte' => 'Accepté',
                                                'refuse' => 'Refusé',
                                                'expire' => 'Expiré',
                                            ])
                                            ->default('brouillon')
                                            ->required()
                                            ->rules([
                                                function (Forms\Get $get, $livewire) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($get, $livewire) {
                                                        $statutsAvecLignes = ['envoye', 'accepte', 'refuse', 'expire'];
                                                        
                                                        if (in_array($value, $statutsAvecLignes)) {
                                                            $lignesDevis = $get('lignesDevis') ?? [];
                                                            
                                                            $lignesValides = collect($lignesDevis)->filter(function ($ligne) {
                                                                return !empty($ligne['libelle']) && !empty($ligne['prix_unitaire']) && !empty($ligne['quantite']);
                                                            })->count();
                                                            
                                                            if ($lignesValides === 0) {
                                                                $fail('Le devis doit contenir au moins une ligne pour passer à ce statut.');
                                                            }
                                                        }
                                                        
                                                        if ($value === 'envoye') {
                                                            if (!$get('date_validite')) {
                                                                $fail('La date de validité est obligatoire pour envoyer le devis.');
                                                            }
                                                            
                                                            if ($get('date_validite') && $get('date_validite') <= today()) {
                                                                $fail('La date de validité doit être supérieure à aujourd\'hui.');
                                                            }
                                                        }
                                                    };
                                                }
                                            ]),
                                        
                                        Forms\Components\DatePicker::make('date_validite')
                                            ->label('Date de validité')
                                            ->default(fn () => now()->addDays(30))
                                            ->minDate(today())
                                            ->helperText('Obligatoire pour envoyer le devis')
                                            ->disabled(fn ($record) => $record && $record->statut !== 'brouillon')
                                            ->rules([
                                                function () {
                                                    return function (string $attribute, $value, \Closure $fail) {
                                                        if ($value && $value <= today()) {
                                                            $fail('La date de validité doit être supérieure à aujourd\'hui.');
                                                        }
                                                    };
                                                }
                                            ]),
                                        Forms\Components\TextInput::make('taux_tva')
                                            ->label('Taux TVA (%)')
                                            ->numeric()
                                            ->step(0.01)
                                            ->default(20.00)
                                            ->suffix('%')
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->helperText('Taux de TVA appliqué au devis')
                                            ->disabled(fn ($record) => $record && $record->statut !== 'brouillon'),
                                    ]),
                            ]),

                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn ($record) => $record && $record->statut !== 'brouillon'),
                    ]),

                    Forms\Components\Section::make('Lignes du devis')
                        ->schema([
                            Forms\Components\Repeater::make('lignesDevis')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\TextInput::make('libelle')
                                                ->label('Libellé')
                                                ->required()
                                                ->columnSpan(2),

                                            Forms\Components\TextInput::make('prix_unitaire')
                                                ->label('Prix unitaire (€)')
                                                ->required()
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('€'),

                                            Forms\Components\TextInput::make('quantite')
                                                ->label('Quantité')
                                                ->required()
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(1),

                                            Forms\Components\Hidden::make('ordre'),
                                        ]),
                                ])
                                ->columns(1)
                                ->defaultItems(1)
                                ->addActionLabel('Ajouter une ligne')
                                ->reorderable('ordre')
                                ->orderColumn('ordre')
                                ->itemLabel(fn (array $state): ?string => $state['libelle'] ?? 'Nouvelle ligne')
                                    ->disabled(fn ($record) => $record && $record->statut !== 'brouillon')
                                    ->deletable(fn ($record) => !$record || $record->statut === 'brouillon')
                                    ->addable(fn ($record) => !$record || $record->statut === 'brouillon'),
                        ])
                        ->collapsible()
                        ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_devis')
                    ->label('N° Devis')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('projet.designation')
                    ->label('Projet')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('projet.client.designation')
                    ->label('Client')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'brouillon' => 'gray',
                        'envoye' => 'warning',
                        'accepte' => 'success',
                        'refuse' => 'danger',
                        'expire' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'brouillon' => 'Brouillon',
                        'envoye' => 'Envoyé',
                        'accepte' => 'Accepté',
                        'refuse' => 'Refusé',
                        'expire' => 'Expiré',
                    }),

                Tables\Columns\TextColumn::make('date_validite')
                    ->label('Validité')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Devis $record): string => 
                        $record->est_expire ? 'danger' : 'gray'
                    )
                    ->icon(fn (Devis $record): ?string => 
                        $record->est_expire ? 'heroicon-o-exclamation-triangle' : null
                    ),

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
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('expires')
                    ->label('Expirés')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('date_validite', '<', now())
                            ->whereIn('statut', ['brouillon', 'envoye'])
                    )
                    ->default(false)
                    ->indicateUsing(fn (array $data): ?string => 
                        $data['isActive'] ?? false ? 'Devis expirés' : null
                    ),

                Tables\Filters\Filter::make('avec_facture')
                    ->label('Avec facture')
                    ->query(fn (Builder $query): Builder => $query->has('facture')),

                Tables\Filters\Filter::make('sans_facture')
                    ->label('Sans facture')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('facture')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Devis $record): bool => $record->canBeModified()),
                Tables\Actions\Action::make('create_facture')
                    ->label('Créer facture')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->visible(fn (Devis $record): bool => $record->statut === 'accepte' && !$record->facture)
                    ->url(fn (Devis $record): string => route('filament.user.resources.factures.create', ['devis' => $record->id_devis])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!$record->canBeDeleted()) {
                                    Notification::make()
                                        ->title('Suppression impossible')
                                        ->body("Le devis #{$record->numero_devis} ne peut pas être supprimé.")
                                        ->danger()
                                        ->send();
                                    return;
                                }
                            }
                            $records->each->delete();
                        }),
                ]),
            ])
            ->defaultSort('numero_devis', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('projet.client', fn ($query) => $query->where('id_utilisateur', Auth::id()));
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
            'index' => Pages\ListDevis::route('/'),
            'create' => Pages\CreateDevis::route('/create'),
            'view' => Pages\ViewDevis::route('/{record}'),
            'edit' => Pages\EditDevis::route('/{record}/edit'),
        ];
    }
}