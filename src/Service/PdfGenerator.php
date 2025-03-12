<?php

namespace App\Service;

use setasign\Fpdi\Fpdi;

class PdfGenerator
{
    public function generateRecap(array $commandes): string
    {
        // Initialiser FPDF
        $pdf = new Fpdi();

        // Ajouter une page
        $pdf->AddPage();

        // Ajouter le logo
        $pdf->Image(__DIR__ . '/../../public/images/logos/logo-mobileworld.png', 10, 10, 40);
        $pdf->Ln(20); // Espace après le logo

        // Définir la police
        $pdf->SetFont('Arial', 'B', 16);

        // Titre principal
        $pdf->Cell(0, 10, 'Mobile World', 0, 1, 'C');
        $pdf->Cell(0, 10, mb_convert_encoding('Récapitulatif de commande', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(10);

        // Informations générales de la commande
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(
            0,
            10,
            mb_convert_encoding('Numéro de commande : ', 'ISO-8859-1', 'UTF-8') . $commandes[0]->getNumero(),
            0,
            1
        );
        $pdf->Cell(0, 10, 'Date : ' . $commandes[0]->getDate()->format('d/m/Y H:i'), 0, 1);
        $pdf->Ln(5);

        // Entête du tableau des produits
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(110, 10, 'Produit', 1);
        $pdf->Cell(20, 10, mb_convert_encoding('Quantité', 'ISO-8859-1', 'UTF-8'), 1);
        $pdf->Cell(30, 10, 'Prix unitaire', 1);
        $pdf->Cell(30, 10, 'Total', 1);
        $pdf->Ln();

        // Contenu du tableau des produits
        $pdf->SetFont('Arial', '', 12);
        $totalGeneral = 0;

        foreach ($commandes as $commande) {
            $produit = $commande->getProduit();
            
            // Vérifier si le statut du produit est "Produit annulé"
            if ($commande->getStatut() === 'Produit annulé par le client') {
                continue; // Ignorer ce produit
            }

            $quantite = $commande->getQuantite();
            $prixUnitaire = $produit->getPrix();
            $totalLigne = $quantite * $prixUnitaire;

            $pdf->Cell(110, 10, mb_convert_encoding($produit->getNom(), 'ISO-8859-1', 'UTF-8'), 1);
            $pdf->Cell(20, 10, $quantite, 1);
            $pdf->Cell(30, 10, $prixUnitaire . mb_convert_encoding(' €', 'Windows-1252', 'UTF-8'), 1);
            $pdf->Cell(30, 10, $totalLigne . mb_convert_encoding(' €', 'Windows-1252', 'UTF-8'), 1);
            $pdf->Ln();

            $totalGeneral += $totalLigne;
        }

        // Total général
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(
            0,
            10,
            'Montant Total TTC : ' . $totalGeneral . mb_convert_encoding(' €', 'Windows-1252', 'UTF-8'),
            0,
            1
        );

        $pdf->Ln(10);

        // Adresse de livraison
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, mb_convert_encoding('Adresse de livraison :', 'ISO-8859-1', 'UTF-8'), 0, 0);
        $pdf->Ln(10); // Espace après le titre

        // Récupérer l'adresse de livraison depuis la première commande
        $commande = $commandes[0]; // toutes les commandes ont la même adresse
        $adresseLivraisonString = sprintf(
            "%s %s\n%s\n%s %s\n%s",
            $commande->getNom(),
            $commande->getPrenom(),
            $commande->getAdresse(),
            $commande->getCodePostal(),
            $commande->getVille(),
            $commande->getTel()
        );

        // Afficher l'adresse de livraison
        $pdf->MultiCell(90, 10, mb_convert_encoding($adresseLivraisonString, 'ISO-8859-1', 'UTF-8'), 0, 'L');

        // Retourner le PDF en tant que chaîne de caractères
        return $pdf->Output('S'); // 'S' signifie "retourner comme chaîne"
    }
}