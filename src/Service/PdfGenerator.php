<?php

namespace App\Service;

use setasign\Fpdi\Fpdi;
use App\Entity\Commande;

class PdfGenerator
{
    public function generateFacture(Commande $commande): string
    {
        // Initialiser FPDF
        $pdf = new Fpdi();

        // Ajouter une page
        $pdf->AddPage();

        $pdf->Image(__DIR__ . '/../../public/images/logos/logo-mobileworld.png', 10, 10, 40);
        $pdf->Ln(10);

        // Définir la police
        $pdf->SetFont('Arial', 'B', 16);

        // Titre
        $pdf->Cell(0, 10, 'Mobile World', 0, 1, 'C');
        $pdf->Cell(0, 10, 'FACTURE', 0, 1, 'C');
        $pdf->Ln(10);

        // Informations de la commande
        $pdf->SetFont('Arial', '', 12);
        // utf-8 pour utiliser accents
        $pdf->Cell(0, 10, mb_convert_encoding('Numéro de commande : ', 'ISO-8859-1', 'UTF-8') . $commande->getNumero(), 0, 1);
        $pdf->Cell(0, 10, 'Date : ' . $commande->getDate()->format('d/m/Y H:i:s'), 0, 1);

        // Informations du produit en forme de tableau
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(80, 10, 'Produit', 1);
        $pdf->Cell(30, 10, mb_convert_encoding('Quantité', 'ISO-8859-1', 'UTF-8'), 1);
        $pdf->Cell(40, 10, 'Prix unitaire', 1);
        $pdf->Cell(40, 10, 'Total', 1);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(80, 10, $commande->getProduit()->getNom(), 1);
        $pdf->Cell(30, 10, $commande->getQuantite(), 1);
        $pdf->Cell(40, 10, $commande->getProduit()->getPrix() . mb_convert_encoding(' €', 'Windows-1252', 'UTF-8'), 1);
        $pdf->Cell(40, 10, ($commande->getQuantite() * $commande->getProduit()->getPrix()) . mb_convert_encoding(' €', 'Windows-1252', 'UTF-8'), 1);
        $pdf->Ln(10);

        // Total
        $total = $commande->getQuantite() * $commande->getProduit()->getPrix();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(
            0,
            10,
            'Montant Total TTC : ' . $total . mb_convert_encoding(' €', 'Windows-1252', 'UTF-8'),
            0,
            1
        );
        // Retourner le PDF en tant que chaîne de caractères
        return $pdf->Output('S'); // 'S' signifie "retourner comme chaîne"
    }
}
