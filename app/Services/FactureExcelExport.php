<?php

namespace App\Services;

use App\Models\Facture;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FactureExcelExport
{
    private Facture $facture;
    private Spreadsheet $spreadsheet;
    private $worksheet;

    public function __construct(Facture $facture)
    {
        $this->facture = $facture;
        $this->spreadsheet = new Spreadsheet();
        $this->worksheet = $this->spreadsheet->getActiveSheet();
    }

    public function download(): BinaryFileResponse
    {
        $this->generateExcel();
        
        $filename = 'Facture_' . $this->facture->numero_facture . '_' . date('d-m-Y') . '.xlsx';
        $tempFile = storage_path('app/temp/' . $filename);
        
        // Créer le dossier temp s'il n'existe pas
        if (!file_exists(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }
        
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Alternative: Export en streaming pour économiser la mémoire
     */
    public function downloadStream(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->generateExcel();
        
        $filename = 'Facture_' . $this->facture->numero_facture . '_' . date('d-m-Y') . '.xlsx';
        
        return response()->streamDownload(function () {
            $writer = new Xlsx($this->spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Sauvegarde le fichier dans storage/app/exports
     */
    public function saveToStorage(): string
    {
        $this->generateExcel();
        
        $filename = 'Facture_' . $this->facture->numero_facture . '_' . date('d-m-Y') . '.xlsx';
        $filePath = storage_path('app/exports/' . $filename);
        
        // Créer le dossier exports s'il n'existe pas
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($filePath);
        
        return $filePath;
    }

    private function generateExcel(): void
    {
        // Configuration de base
        $this->worksheet->setTitle('Facture ' . $this->facture->numero_facture);
        
        // Largeurs des colonnes (basées sur le modèle devis)
        $this->worksheet->getColumnDimension('A')->setWidth(23.29);
        $this->worksheet->getColumnDimension('B')->setWidth(23.14);
        $this->worksheet->getColumnDimension('C')->setWidth(12.57);
        $this->worksheet->getColumnDimension('D')->setWidth(22.29);
        $this->worksheet->getColumnDimension('E')->setWidth(19.57);
        $this->worksheet->getColumnDimension('F')->setWidth(19.57);

        // Hauteurs des lignes spécifiques
        $this->worksheet->getRowDimension(1)->setRowHeight(18.75);
        $this->worksheet->getRowDimension(4)->setRowHeight(18.75);
        $this->worksheet->getRowDimension(12)->setRowHeight(15.75);

        // Génération du contenu
        $this->addHeader();
        $this->addCompanyAndClientInfo();
        $this->addFactureObject();
        $this->addItemsTable();
        $this->addDetailedTotals();
    }

    private function addHeader(): void
    {
        // Numéro de facture (ligne 1)
        $this->worksheet->setCellValue('A1', 'Numéro de Facture');
        $this->worksheet->setCellValue('B1', $this->facture->numero_facture);
        $this->worksheet->setCellValue('E1', 'Date d\'édition');
        $this->worksheet->setCellValue('F1', $this->facture->date_edition->format('d/m/Y'));
        
        // Date d'échéance (ligne 2)
        if ($this->facture->date_paiement_limite) {
            $this->worksheet->setCellValue('E2', 'Date d\'échéance');
            $this->worksheet->setCellValue('F2', $this->facture->date_paiement_limite->format('d/m/Y'));
        }

        // Date de paiement (ligne 3) si payée
        if ($this->facture->etat_facture === 'payee' && $this->facture->date_paiement_effectif) {
            $this->worksheet->setCellValue('E3', 'Date de paiement');
            $this->worksheet->setCellValue('F3', $this->facture->date_paiement_effectif->format('d/m/Y'));
        }
        
        // Style de l'en-tête
        $this->worksheet->getStyle('A1:F3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
        ]);
    }

    private function addCompanyAndClientInfo(): void
    {
        $client = $this->facture->devis->projet->client;
        $clientAdresse = $client->adresse;
        $utilisateur = $client->utilisateur;
        $utilisateurAdresse = $utilisateur->adresse;

        // Section entreprise (lignes 6-10, colonnes A-B)
        $this->worksheet->setCellValue('A6', 'Nom de l\'entreprise');
        $this->worksheet->setCellValue('B6', $utilisateur->name . ' ' . $utilisateur->prenom);
        
        $this->worksheet->setCellValue('A7', 'Adresse');
        if ($utilisateurAdresse) {
            $adresseEntreprise = $utilisateurAdresse->ligne1;
            if ($utilisateurAdresse->ligne2) {
                $adresseEntreprise .= " " . $utilisateurAdresse->ligne2;
            }
            if ($utilisateurAdresse->ligne3) {
                $adresseEntreprise .= " " . $utilisateurAdresse->ligne3;
            }
            $this->worksheet->setCellValue('B7', $adresseEntreprise);
        }
        
        $this->worksheet->setCellValue('A8', 'Code Postal et Ville');
        if ($utilisateurAdresse) {
            $this->worksheet->setCellValue('B8', $utilisateurAdresse->code_postal . ' ' . $utilisateurAdresse->ville);
        }
        
        $this->worksheet->setCellValue('A9', 'Numéro de téléphone');
        $this->worksheet->setCellValue('B9', $utilisateur->telephone ?? '');
        
        $this->worksheet->setCellValue('A10', 'Email');
        $this->worksheet->setCellValue('B10', $utilisateur->email);

        // Section client (lignes 6-10, colonnes D-F avec fusion E:F)
        $this->worksheet->setCellValue('D6', 'Nom du client');
        $this->worksheet->setCellValue('E6', $client->designation);
        $this->worksheet->mergeCells('E6:F6');
        
        $this->worksheet->setCellValue('D7', 'Adresse');
        $adresseClient = $clientAdresse->ligne1;
        if ($clientAdresse->ligne2) {
            $adresseClient .= " " . $clientAdresse->ligne2;
        }
        if ($clientAdresse->ligne3) {
            $adresseClient .= " " . $clientAdresse->ligne3;
        }
        $this->worksheet->setCellValue('E7', $adresseClient);
        $this->worksheet->mergeCells('E7:F7');
        
        $this->worksheet->setCellValue('D8', 'Code Postal et Ville');
        $this->worksheet->setCellValue('E8', $clientAdresse->code_postal . ' ' . $clientAdresse->ville);
        $this->worksheet->mergeCells('E8:F8');
        
        $this->worksheet->setCellValue('D9', 'Numéro de téléphone');
        $this->worksheet->setCellValue('E9', $client->telephone ?? '');
        $this->worksheet->mergeCells('E9:F9');
        
        $this->worksheet->setCellValue('D10', 'Email');
        $this->worksheet->setCellValue('E10', $client->email);
        $this->worksheet->mergeCells('E10:F10');
    }

    private function addFactureObject(): void
    {
        // Ligne 12 : Objet de la facture (basé sur le projet)
        $this->worksheet->setCellValue('A12', 'Objet : Facture - ' . $this->facture->devis->projet->designation);
        $this->worksheet->getStyle('A12')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11]
        ]);
    }

    private function addItemsTable(): void
    {
        // En-têtes du tableau (ligne 14)
        $this->worksheet->setCellValue('A14', 'Description');
        $this->worksheet->setCellValue('D14', 'Quantité');
        $this->worksheet->setCellValue('E14', 'Prix Unitaire HT');
        $this->worksheet->setCellValue('F14', 'Total HT');
        
        // Fusion de A14:C14 pour Description
        $this->worksheet->mergeCells('A14:C14');
        
        // Style des en-têtes
        $this->worksheet->getStyle('A14:F14')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Lignes de données (à partir de la ligne 15)
        $currentRow = 15;
        foreach ($this->facture->lignesFacturation as $ligne) {
            $this->worksheet->setCellValue('A' . $currentRow, $ligne->libelle);
            $this->worksheet->setCellValue('D' . $currentRow, $ligne->quantite);
            
            // Format des prix avec virgule comme séparateur décimal et € 
            $this->worksheet->setCellValue('E' . $currentRow, $ligne->prix_unitaire);
            $this->worksheet->getStyle('E' . $currentRow)->getNumberFormat()
                ->setFormatCode('#,##0.00 "€"');
            
            $total = $ligne->quantite * $ligne->prix_unitaire;
            $this->worksheet->setCellValue('F' . $currentRow, $total);
            $this->worksheet->getStyle('F' . $currentRow)->getNumberFormat()
                ->setFormatCode('#,##0.00 "€"');
            
            // Fusion des cellules pour la description (A:C)
            $this->worksheet->mergeCells('A' . $currentRow . ':C' . $currentRow);
            
            // Style des cellules de données
            $this->worksheet->getStyle('A' . $currentRow . ':F' . $currentRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
            
            // Alignement à droite pour les montants
            $this->worksheet->getStyle('D' . $currentRow . ':F' . $currentRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]);
            
            // Hauteur de ligne
            $this->worksheet->getRowDimension($currentRow)->setRowHeight(20);
            
            $currentRow++;
        }
    }

    private function addDetailedTotals(): void
    {
        $lignesCount = $this->facture->lignesFacturation->count();
        $startTotalRow = 15 + $lignesCount + 3; // +3 pour laisser de l'espace
        
        // Calculs utilisant les méthodes du modèle
        $montantTotalHT = $this->facture->montant_total_ht;
        $remiseHT = 0.00; // À adapter selon vos besoins
        $totalNetHT = $montantTotalHT - $remiseHT;
        $totalTVA = $this->facture->montant_tva;
        $montantTotalTTC = $this->facture->montant_total_ttc;
        
        // Montant Total HT
        $this->worksheet->setCellValue('D' . $startTotalRow, 'Montant Total HT');
        $this->worksheet->setCellValue('F' . $startTotalRow, $montantTotalHT);
        $this->worksheet->mergeCells('D' . $startTotalRow . ':E' . $startTotalRow);
        
        // Remise HT
        $this->worksheet->setCellValue('D' . ($startTotalRow + 1), 'Remise HT');
        $this->worksheet->setCellValue('F' . ($startTotalRow + 1), $remiseHT);
        $this->worksheet->mergeCells('D' . ($startTotalRow + 1) . ':E' . ($startTotalRow + 1));
        
        // Total Net HT
        $this->worksheet->setCellValue('D' . ($startTotalRow + 2), 'Total Net HT');
        $this->worksheet->setCellValue('F' . ($startTotalRow + 2), $totalNetHT);
        $this->worksheet->mergeCells('D' . ($startTotalRow + 2) . ':E' . ($startTotalRow + 2));
        
        // Total TVA
        $this->worksheet->setCellValue('D' . ($startTotalRow + 3), 'Total TVA (' . $this->facture->taux_tva . '%)');
        $this->worksheet->setCellValue('F' . ($startTotalRow + 3), $totalTVA);
        $this->worksheet->mergeCells('D' . ($startTotalRow + 3) . ':E' . ($startTotalRow + 3));
        
        // Montant Total TTC
        $this->worksheet->setCellValue('D' . ($startTotalRow + 4), 'Montant Total TTC');
        $this->worksheet->setCellValue('F' . ($startTotalRow + 4), $montantTotalTTC);
        $this->worksheet->mergeCells('D' . ($startTotalRow + 4) . ':E' . ($startTotalRow + 4));
        
        // Format des montants et bordures fines pour toutes les lignes de totaux
        for ($i = 0; $i <= 4; $i++) {
            $this->worksheet->getStyle('F' . ($startTotalRow + $i))->getNumberFormat()
                ->setFormatCode('#,##0.00 "€"');
            
            // Ajouter des bordures fines pour chaque ligne de total
            $this->worksheet->getStyle('D' . ($startTotalRow + $i) . ':F' . ($startTotalRow + $i))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
            
            // Style coloré pour la colonne de gauche (labels des totaux)
            $this->worksheet->getStyle('D' . ($startTotalRow + $i) . ':E' . ($startTotalRow + $i))->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ]
            ]);
            
            // Alignement à droite pour les montants
            $this->worksheet->getStyle('F' . ($startTotalRow + $i))->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]);
        }
        
        // Style spécial pour le total TTC (dernière ligne) - bleu plus foncé
        $this->worksheet->getStyle('D' . ($startTotalRow + 4) . ':E' . ($startTotalRow + 4))->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E5AAA']
            ]
        ]);

        // Note si présente
        if ($this->facture->note) {
            $noteRow = $startTotalRow + 7;
            $this->worksheet->setCellValue('A' . $noteRow, 'Note :');
            $this->worksheet->setCellValue('A' . ($noteRow + 1), $this->facture->note);
            $this->worksheet->getStyle('A' . $noteRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11]
            ]);
            
            // Style pour la note
            $this->worksheet->getStyle('A' . ($noteRow + 1))->applyFromArray([
                'alignment' => ['wrapText' => true]
            ]);
            
            // Fusionner les cellules pour la note si elle est longue
            $this->worksheet->mergeCells('A' . ($noteRow + 1) . ':F' . ($noteRow + 1));
        }

        // Informations de paiement si la facture est payée
        if ($this->facture->etat_facture === 'payee') {
            $paymentRow = $startTotalRow + ($this->facture->note ? 10 : 7);
            $this->worksheet->setCellValue('A' . $paymentRow, 'FACTURE PAYÉE');
            if ($this->facture->type_paiement) {
                $this->worksheet->setCellValue('A' . ($paymentRow + 1), 'Mode de paiement : ' . ucfirst($this->facture->type_paiement));
            }
            
            $this->worksheet->getStyle('A' . $paymentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '00AA00']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $this->worksheet->mergeCells('A' . $paymentRow . ':F' . $paymentRow);
        }
    }
}