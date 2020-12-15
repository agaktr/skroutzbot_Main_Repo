<?php

namespace App\Repository;

use App\Entity\Price;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Price|null find($id, $lockMode = null, $lockVersion = null)
 * @method Price|null findOneBy(array $criteria, array $orderBy = null)
 * @method Price[]    findAll()
 * @method Price[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Price::class);
    }

    public function findByProductUUids($ids)
    {

        $queryIds = '';
        foreach ($ids as $uuid){
            $queryIds .= '"'.$uuid.'",';
        }
        $queryIds = substr($queryIds,0,-1);

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Price::class, 'p');
        $q = 'SELECT * FROM `Price` WHERE `product_uuid` IN ('.$queryIds.')';

        $query = $this->getEntityManager()->createNativeQuery($q, $rsm);
        return $query->getResult();
    }

    // /**
    //  * @return Price[] Returns an array of Price objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Price
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}