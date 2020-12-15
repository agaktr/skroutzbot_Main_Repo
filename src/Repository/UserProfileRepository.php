<?php

namespace App\Repository;

use App\Entity\UserProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserProfile[]    findAll()
 * @method UserProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offsest = null)
 */
class UserProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProfile::class);
    }

    public function getUndone(){

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(UserProfile::class, 's');
        $q = 'SELECT * FROM `user_profile` WHERE `is_done` = 0 AND `items_processed` = 0 AND `items_number` = 0 LIMIT 1 OFFSET 0';

        $query = $this->getEntityManager()->createNativeQuery($q, $rsm);
        return $query->getResult();
    }

    public function getUnprocessed(){

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(UserProfile::class, 's');
        $q = 'SELECT * FROM `user_profile` WHERE `is_done` = 0 AND `items_number` > 0 ORDER BY `items_processed` DESC';

        $query = $this->getEntityManager()->createNativeQuery($q, $rsm);
        return $query->getResult();
    }

    public function getByUuids($ids){

        $queryIds = '';
        foreach ($ids as $uuid){
            $queryIds .= '"'.$uuid.'",';
        }
        $queryIds = substr($queryIds,0,-1);

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(UserProfile::class, 's');
        $q = 'SELECT * FROM `user_profile` WHERE `uuid` IN ('.$queryIds.')';

        $query = $this->getEntityManager()->createNativeQuery($q, $rsm);
        return $query->getResult();
    }

    // /**
    //  * @return UserProfile[] Returns an array of UserProfile objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserProfile
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
