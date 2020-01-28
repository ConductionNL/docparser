<?php

namespace App\Repository;

use App\Entity\ApiDoc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ApiDoc|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiDoc|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiDoc[]    findAll()
 * @method ApiDoc[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiDocsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiDoc::class);
    }

    // /**
    //  * @return ApiDoc[] Returns an array of ApiDoc objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ApiDoc
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
