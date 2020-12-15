<?php

namespace App\Repository;

use App\Entity\ProfileRawData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProfileRawData|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProfileRawData|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProfileRawData[]    findAll()
 * @method ProfileRawData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProfileRawDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfileRawData::class);
    }

    public function getUndone()
    {

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(ProfileRawData::class, 's');
        $q = 'SELECT * FROM `profile_raw_data` LIMIT 10 OFFSET 0';

        $query = $this->getEntityManager()->createNativeQuery($q, $rsm);
        return $query->getResult();
    }

    // /**
    //  * @return ProfileRawData[] Returns an array of ProfileRawData objects
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
    public function findOneBySomeField($value): ?ProfileRawData
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
