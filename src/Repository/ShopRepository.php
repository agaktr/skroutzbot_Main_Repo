<?php

namespace App\Repository;

use App\Entity\Shop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Shop|null find($id, $lockMode = null, $lockVersion = null)
 * @method Shop|null findOneBy(array $criteria, array $orderBy = null)
 * @method Shop[]    findAll()
 * @method Shop[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shop::class);
    }

    /**
    * @return Shop[] Returns an array of Shop objects
    */

    public function findByIds($ids)
    {

        $queryIds = implode(',',$ids);

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Shop::class, 's');
        $q = 'SELECT * FROM `shop` WHERE `external_id` IN ('.$queryIds.')';

        $query = $this->getEntityManager()->createNativeQuery($q, $rsm);
        return $query->getResult();
    }

    public function search($string)
    {

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Shop::class, 's');
        $q = 'SELECT * FROM `shop` WHERE `name` LIKE "'.$string.'%"';

        $query = $this->getEntityManager()->createNativeQuery($q, $rsm);
        return $query->getResult();
    }


    /*
    public function findOneBySomeField($value): ?Shop
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
