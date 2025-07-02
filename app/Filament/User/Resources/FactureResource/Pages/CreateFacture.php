<?php

namespace App\Filament\User\Resources\FactureResource\Pages;

use App\Filament\User\Resources\FactureResource;
use App\Models\Devis;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Actions;

class CreateFacture extends CreateRecord
{
    protected static string $resource = FactureResource::class;

    public ?Devis $devis = null;

    public function mount(): void
    {
        $devisId = request()->query('devis');
        
        if (!$devisId) {
            Notification::make()
                ->title('Accès refusé')
                ->body('Les factures ne peuvent être créées que depuis un devis accepté.')
                ->danger()
                ->send();
                
            $this->redirect(FactureResource::getUrl('index'));
            return;
        }

        $this->devis = Devis::with(['lignesDevis', 'projet.client'])
            ->whereHas('projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
            ->where('statut', 'accepte')
            ->find($devisId);

        if (!$this->devis) {
            Notification::make()
                ->title('Devis introuvable')
                ->body('Le devis spécifié n\'existe pas ou n\'est pas accepté.')
                ->danger()
                ->send();
                
            $this->redirect(FactureResource::getUrl('index'));
            return;
        }

        parent::mount();

        if ($this->devis) {
            $this->form->fill([
                'id_devis' => $this->devis->id_devis,
                'numero_facture' => \App\Models\Facture::getNextNumeroFacture(Auth::id()),
                'taux_tva' => $this->devis->taux_tva,
                'date_edition' => today()->toDateString(),
                'date_paiement_limite' => today()->addDays(30)->toDateString(),
                'etat_facture' => 'brouillon',
                'note' => $this->devis->note,
            ]);
        }
    }

    public function loadLignesFromDevis(): void
    {
        if ($this->devis && $this->devis->lignesDevis->isNotEmpty()) {
            $lignes = $this->devis->lignesDevis->map(function ($ligne) {
                return [
                    'libelle' => $ligne->libelle,
                    'prix_unitaire' => $ligne->prix_unitaire,
                    'quantite' => $ligne->quantite,
                    'ordre' => $ligne->ordre,
                ];
            })->toArray();
            
            $this->form->fill([
                'lignesFacturation' => $lignes
            ]);
            
            Notification::make()
                ->title('Lignes chargées')
                ->body(count($lignes) . ' ligne(s) du devis ont été chargées.')
                ->success()
                ->send();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['lignesFacturation']) || count($data['lignesFacturation']) === 0) {
            Notification::make()
                ->title('Erreur de validation')
                ->body('La facture doit contenir au moins une ligne de facturation.')
                ->danger()
                ->send();
                
            $this->halt();
        }

        $lignesValides = collect($data['lignesFacturation'])->filter(function ($ligne) {
            return !empty($ligne['libelle']) && 
                   !empty($ligne['prix_unitaire']) && 
                   !empty($ligne['quantite']) && 
                   $ligne['quantite'] > 0;
        });

        if ($lignesValides->isEmpty()) {
            Notification::make()
                ->title('Erreur de validation')
                ->body('La facture doit contenir au moins une ligne valide (libellé, prix unitaire et quantité > 0).')
                ->danger()
                ->send();
                
            $this->halt();
        }

        if ($this->devis) {
            $data['id_devis'] = $this->devis->id_devis;
            $data['taux_tva'] = $this->devis->taux_tva;
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Actions à effectuer après la création si nécessaire
    }

    public function getTitle(): string
    {
        return $this->devis 
            ? "Créer une facture depuis le devis #{$this->devis->numero_devis}"
            : 'Créer une facture';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreateFormDefaults(): array
    {
        $defaults = [
            'numero_facture' => \App\Models\Facture::getNextNumeroFacture(Auth::id()),
            'date_edition' => today()->toDateString(),
            'date_paiement_limite' => today()->addDays(30)->toDateString(),
            'etat_facture' => 'brouillon',
        ];

        if ($this->devis) {
            $defaults['id_devis'] = $this->devis->id_devis;
            $defaults['taux_tva'] = $this->devis->taux_tva;
            $defaults['note'] = $this->devis->note;
            
            $defaults['lignesFacturation'] = $this->devis->lignesDevis->map(function ($ligne) {
                return [
                    'libelle' => $ligne->libelle,
                    'prix_unitaire' => $ligne->prix_unitaire,
                    'quantite' => $ligne->quantite,
                    'ordre' => $ligne->ordre,
                ];
            })->toArray();
        }

        return $defaults;
    }

    protected function getFormActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Créer')
                ->submit('create'),
            Actions\Action::make('cancel')
                ->label('Annuler')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}