<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;

/**
 * @extends ServiceEntityRepository<RssFeed>
 */
#[AsRepository(entityClass: RssFeed::class)]
final class RssFeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RssFeed::class);
    }

    public function findByUrl(string $url): ?RssFeed
    {
        /** @var RssFeed|null */
        return $this->createQueryBuilder('r')
            ->andWhere('r.url = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return RssFeed[]
     */
    public function findActiveFeeds(): array
    {
        /** @var RssFeed[] */
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function existsByUrl(string $url): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.url = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    /**
     * @param RssFeed[] $feeds
     */
    public function batchInsert(array $feeds): void
    {
        $batchSize = 50;
        $i = 0;

        foreach ($feeds as $feed) {
            $this->getEntityManager()->persist($feed);

            if (($i % $batchSize) === 0) {
                $this->getEntityManager()->flush();
            }

            ++$i;
        }

        $this->getEntityManager()->flush();
    }

    public function save(RssFeed $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RssFeed $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
