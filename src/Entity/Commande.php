<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    private ?Produit $produit = null;

    #[ORM\Column]
    private ?string $numero = null;

    // Partie adresse de livraison
    #[ORM\Column(length: 100, nullable: false)]
    #[Assert\Regex(pattern: '/^[\p{L}\s\-]+$/u', message: 'Le nom ne doit contenir que des lettres, espaces ou tirets.')]
    private string $nom;

    #[ORM\Column(length: 100, nullable: false)]
    #[Assert\Regex(pattern: '/^[\p{L}\s\-]+$/u', message: 'Le prénom ne doit contenir que des lettres, espaces ou tirets.')]
    private string $prenom;

    #[ORM\Column(length: 150, nullable: false)] 
    private string $adresse;

    #[ORM\Column(length: 100, nullable: false)]
    #[Assert\Regex(pattern: '/^[\p{L}\s\-]+$/u', message: 'La ville ne doit contenir que des lettres, espaces ou tirets.')]
    private string $ville;

    #[ORM\Column(length: 5, nullable: false)] // Code postal sur 5 caractères
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir exactement 5 chiffres.')]
    private string $codePostal;

    #[ORM\Column(length: 10, nullable: false)] // Numéro de téléphone sur 10 chiffres
    #[Assert\Regex(pattern: '/^0\d{9}$/', message: 'Le numéro de téléphone doit contenir exactement 10 chiffres et commencer par un zéro.')]
    private string $tel;

    // Getters et setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;
        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;
        return $this;
    }

    // Partie adresse de livraison
    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getCodePostal(): string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): static
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getTel(): string
    {
        return $this->tel;
    }

    public function setTel(string $tel): static
    {
        $this->tel = $tel;
        return $this;
    }
}