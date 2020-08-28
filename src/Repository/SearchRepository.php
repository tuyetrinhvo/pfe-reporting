<?php

namespace App\Repository;

use App\Entity\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Search|null find($id, $lockMode = null, $lockVersion = null)
 * @method Search|null findOneBy(array $criteria, array $orderBy = null)
 * @method Search[]    findAll()
 * @method Search[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Search::class);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function findSearchByDateOnYearAgo()
    {
        $now = new \DateTime('now');
        $oneYearAgo = $now->modify('-1 year');

        return $this->createQueryBuilder('s')
            ->andWhere('s.date <= :val')
            ->setParameter('val', $oneYearAgo)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLastSearchById()
    {
        $qu = $this->createQueryBuilder('s');
        $qu->setMaxResults(1);
        $qu->orderBy('s.id', 'DESC');

        return $qu->getQuery()
            ->getSingleResult();
    }

}
