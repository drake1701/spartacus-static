<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;

use Doctrine\ORM\Query\Expr;
use Paperroll\Model;

class Entry extends Generic
{
    /**
     * @return Model\Entry
     */
    public function getLastPublishedEntry() {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->where($qb->expr()->isNotNull('e.published'))
            ->orderBy('e.publishedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return array_pop($query->getResult());
    }

    /**
     * @return array
     */
    public function getPublishable() {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->where($qb->expr()->isNull('e.published'))
            ->andWhere("e.publishedAt < date('now')")
            ->orderBy('e.publishedAt', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @param Model\Entry $entry
     */
    public function rePublish(Model\Entry $entry) {
        $this->logger->debug("Mark {$entry->getTitle()} ({$entry->getId()}) to be republished.");
        $entry->setPublished(null);
    }

    /**
     * @return array
     */
    public function getYears()
    {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->addSelect('year(e.publishedAt) as year')
            ->addSelect('count(e.id) as num')
            ->orderBy('e.publishedAt', 'DESC')
            ->groupBy('year')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @return bool|Model\Entry
     */
    public function getLastPublishedCalendar()
    {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->addSelect('k.path as kind')
            ->join(Model\Image::class, 'i', Expr\Join::WITH, 'i.entryId = e.id')
            ->join(Model\ImageKind::class, 'k', Expr\Join::WITH, 'i.kind = k.id')
            ->where("e.queue = 2 AND date(e.publishedAt) <= date('now')")
            ->orderBy('k.position', 'ASC')
            ->addOrderBy('e.publishedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        $row = array_pop($query->getResult());
        if(count($row)) {
            $entry = array_shift($row);
            foreach($row as $key => $value)
                $entry->$key = $value;

            return $entry;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getTopTen() {

        /** @var \Paperroll\Model\Repository\Tag $tags */
        $tags = $this->getEntityManager()->getRepository(Model\Tag::class);
        $topTen = $tags->getTopTen();

        $entries = [];
        /** @var Model\Tag $tag */
        foreach($topTen as $tag) {
            $entries[] = $tags->getRandom($tag->getId());
        }
        return $entries;
    }


    /**
     * @param Model\Entry $entry
     * @return Model\Entry
     */
    public function getNext(Model\Entry $entry)
    {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->join(Model\Entry::class, 'o', Expr\Join::WITH, "o.id = {$entry->getId()}")
            ->where('e.publishedAt > o.publishedAt')
            ->orderBy('e.publishedAt', 'asc')
            ->setMaxResults(1)
            ->getQuery();

        return $query->getSingleResult();
    }

    /**
     * @param Model\Entry $entry
     * @return Model\Entry
     */
    public function getPrev(Model\Entry $entry)
    {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->join(Model\Entry::class, 'o', Expr\Join::WITH, "o.id = {$entry->getId()}")
            ->where('e.publishedAt < o.publishedAt')
            ->orderBy('e.publishedAt', 'desc')
            ->setMaxResults(1)
            ->getQuery();

        return $query->getSingleResult();
    }

}