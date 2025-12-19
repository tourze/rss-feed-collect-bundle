<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Entity\RssItem;

/**
 * RSS文章数据访问层
 * 基于link字段天然去重，提供RSS文章CRUD和查询功能
 *
 * @extends ServiceEntityRepository<RssItem>
 */
#[AsRepository(entityClass: RssItem::class)]
final class RssItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RssItem::class);
    }

    /**
     * 根据链接查找RSS文章(去重依据)
     */
    public function findByLink(string $link): ?RssItem
    {
        /** @var RssItem|null */
        return $this->createQueryBuilder('r')
            ->andWhere('r.link = :link')
            ->setParameter('link', $link)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 检查链接是否已存在(去重检查)
     */
    public function existsByLink(string $link): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.link = :link')
            ->setParameter('link', $link)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    /**
     * 根据RSS源查找文章列表
     *
     * @return RssItem[]
     */
    public function findByRssFeed(RssFeed $rssFeed, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.rssFeed = :rssFeed')
            ->setParameter('rssFeed', $rssFeed)
            ->orderBy('r.publishTime', 'DESC')
            ->addOrderBy('r.createTime', 'DESC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        /** @var RssItem[] */
        return $qb->getQuery()->getResult();
    }

    /**
     * 统计RSS源的文章总数
     */
    public function countByRssFeed(RssFeed $rssFeed): int
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.rssFeed = :rssFeed')
            ->setParameter('rssFeed', $rssFeed)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 查找最近的文章
     *
     * @return RssItem[]
     */
    public function findRecentItems(int $limit = 20, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.publishTime', 'DESC')
            ->addOrderBy('r.createTime', 'DESC')
            ->setMaxResults($limit)
        ;

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        /** @var RssItem[] */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据GUID查找文章
     */
    public function findByGuid(string $guid): ?RssItem
    {
        /** @var RssItem|null */
        return $this->createQueryBuilder('r')
            ->andWhere('r.guid = :guid')
            ->setParameter('guid', $guid)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据时间范围查找文章
     *
     * @return RssItem[]
     */
    public function findByDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?int $limit = null,
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.publishTime >= :startDate')
            ->andWhere('r.publishTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.publishTime', 'DESC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var RssItem[] */
        return $qb->getQuery()->getResult();
    }

    /**
     * 批量插入RSS文章(优化性能)
     *
     * @param RssItem[] $items
     */
    public function batchInsert(array $items): void
    {
        $batchSize = 50;
        $i = 0;

        foreach ($items as $item) {
            $this->getEntityManager()->persist($item);

            if (($i % $batchSize) === 0) {
                $this->getEntityManager()->flush();
            }

            ++$i;
        }

        $this->getEntityManager()->flush();
    }

    /**
     * 公开flush方法用于批量操作
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * 保存RSS文章
     */
    public function save(RssItem $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除RSS文章
     */
    public function remove(RssItem $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 清理过期文章(可用于定期清理)
     */
    public function removeOlderThan(\DateTimeInterface $date): int
    {
        /** @var int */
        return $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.createTime < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute()
        ;
    }
}
