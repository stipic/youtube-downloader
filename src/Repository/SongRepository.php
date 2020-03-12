<?php

namespace App\Repository;

use App\Entity\Song;
use App\Entity\User;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Song|null find($id, $lockMode = null, $lockVersion = null)
 * @method Song|null findOneBy(array $criteria, array $orderBy = null)
 * @method Song[]    findAll()
 * @method Song[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Song::class);
    }

    // /**
    //  * @return Song[] Returns an array of Song objects
    //  */
    public function findUserSongs($query, User $user, $limit = -1)
    {
        $dql = $this->createQueryBuilder('s')
            ->where(':userId MEMBER OF s.users')
            ->setParameter('userId', $user->getId());

        if(!empty($query))
        {
            $dql->andWhere('s.title LIKE :query');
            $dql->setParameter('query', '%' . $query . '%');
        }

        if($limit != -1)
        {
            $dql->setMaxResults($limit);
        }
        
        
        $query = $dql->orderBy('s.id', 'DESC')->getQuery();
        // $query->useResultCache(true, 30); <- mora se napraviti invalidacija ručno. https://www.gregfreeman.io/2012/invalidating-the-result-cache-in-doctrine-symfony2/
        return $query;
    }

    /*
    public function findOneBySomeField($value): ?Song
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
