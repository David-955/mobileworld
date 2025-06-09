<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function findUniqueCommandeNumeros(Utilisateur $user): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT c.numero, c.date')
            ->andWhere('c.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('c.date', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function findCommandes(string $numero, Utilisateur $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.numero = :numero')
            ->andWhere('c.utilisateur = :user')
            ->setParameter('numero', $numero)
            ->setParameter('user', $user)
            ->leftJoin('c.produit', 'p')
            ->addSelect('p')
            ->getQuery()
            ->getResult();
    }
}
