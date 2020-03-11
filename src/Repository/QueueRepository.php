<?php

namespace App\Repository;

use App\Entity\Queue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Queue|null find($id, $lockMode = null, $lockVersion = null)
 * @method Queue|null findOneBy(array $criteria, array $orderBy = null)
 * @method Queue[]    findAll()
 * @method Queue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Queue::class);
    }

    // /**
    //  * @return Queue[] Returns an array of Queue objects
    //  */
    public function findQueueNumber($userId)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'select COUNT(*) as num from queue where id < (select min(id) from queue where user_id=:user_id and finished=0) and finished=0 LIMIT 1';
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetch();
    }

    /*
    public function findOneBySomeField($value): ?Queue
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
