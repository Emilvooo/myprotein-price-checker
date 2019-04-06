<?php

namespace App\Repository;

use App\Entity\ScrapeableProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ScrapeableProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScrapeableProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScrapeableProduct[]    findAll()
 * @method ScrapeableProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScrapeableProductRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ScrapeableProduct::class);
    }

    // /**
    //  * @return ScrapeableProduct[] Returns an array of ScrapeableProduct objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ScrapeableProduct
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
