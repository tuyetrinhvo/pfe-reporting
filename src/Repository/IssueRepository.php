<?php

namespace App\Repository;

use App\Entity\Issue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Issue|null find($id, $lockMode = null, $lockVersion = null)
 * @method Issue|null findOneBy(array $criteria, array $orderBy = null)
 * @method Issue[]    findAll()
 * @method Issue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IssueRepository extends ServiceEntityRepository
{
    /**
     * IssueRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Issue::class);
    }

    /**
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLastIssueById()
    {
        return $this->createQueryBuilder('i')
            ->setMaxResults(1)
            ->orderBy('i.id', 'DESC')
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param $dateBegin
     * @param $dateEnd
     * @return mixed
     */
    public function findIssuesByClosedOnBetween($dateBegin, $dateEnd)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.closedOn BETWEEN :dateBegin AND :dateEnd')
            ->setParameter('dateBegin', $dateBegin)
            ->setParameter(':dateEnd', $dateEnd)
            ->orderBy('i.closedOn', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param $date
     * @return mixed
     */
    public function findIssuesByClosedOn($date)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.closedOn LIKE :date')
            ->setParameter('date', '%'. $date . '%')
            ->orderBy('i.closedOn', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param $dateBegin
     * @param $dateEnd
     * @return mixed
     */
    public function findIssuesByCreatedOnBetween($dateBegin, $dateEnd)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.createdOn BETWEEN :dateBegin AND :dateEnd')
            ->setParameter('dateBegin', $dateBegin)
            ->setParameter(':dateEnd', $dateEnd)
            ->orderBy('i.createdOn', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param $date
     * @return mixed
     */
    public function findIssuesByCreatedOn($date)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.createdOn LIKE :date')
            ->setParameter('date', '%'. $date . '%')
            ->orderBy('i.createdOn', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

}
