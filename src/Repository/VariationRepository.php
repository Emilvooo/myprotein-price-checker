<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Variation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Variation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Variation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Variation[]    findAll()
 * @method Variation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VariationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Variation::class);
    }

    public function getVariations(Product $product)
    {
        return $this->createQueryBuilder('v')
            ->select('v, pr')
            ->leftJoin('v.prices', 'pr')
            ->andWhere('v.product = :product')
            ->setParameter('product', $product)
            ->andWhere('pr.date > :date')
            ->setParameter('date', new \DateTime('-1 month'))
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Variation[] Returns an array of Variation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Variation
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
