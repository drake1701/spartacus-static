<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;

use Doctrine\ORM\Query\Expr;
use Paperroll\Admin\Controller\Queue;
use Paperroll\Model;

class Entry extends Generic
{
    /**
     * @param null $type
     * @return Model\Entry
     */
    public function getLastPublishedEntry($type = null) {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->where($qb->expr()->isNotNull('e.published'))
            ->orderBy('e.publishedAt', 'DESC')
            ->setMaxResults(1);

        if($type)
            $query->andWhere($qb->expr()->eq('e.queue', $type));

        return array_pop($query->getQuery()->getResult());
    }

    /**
     * @param bool $all
     * @return array
     */
    public function getPublishable($all = false) {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->where("date(e.publishedAt) <= date('now')")
            ->orderBy('e.publishedAt', 'DESC');

        if($all) {
            $result = $query->getQuery()->iterate();
        } else {
            $query->andWhere($qb->expr()->isNull('e.published'));
            $result = $query->getQuery()->getResult();
            if (count($result)) {
                $result[] = $this->getPrev($result[0]);
            }
        }
        return $result;
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
        return $this->getLastPublishedEntry(Model\Queue::CALENDAR);
    }

    /**
     * @param array $excludeIds
     * @return array
     */
    public function getTopTen($excludeIds = []) {

        /** @var \Paperroll\Model\Repository\Tag $tags */
        $tags = $this->getEntityManager()->getRepository(Model\Tag::class);
        return $tags->getTopTen();

    }

    public function rePublishAll() {
        $entries = $this->findAll();
        foreach($entries as $entry)
            $this->rePublish($entry);
        $this->getEntityManager()->flush();
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

        return array_pop($query->getResult());
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

        return array_pop($query->getResult());
    }

    /**
     * @param array $visibleIds
     * @param int $count
     * @return array
     */
    public function getRandom($visibleIds, $count) {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->where($qb->expr()->isNotNull('e.published'))
            ->andWhere($qb->expr()->notIn('e.id', $visibleIds))
            ->setMaxResults($count * 5)
            ->getQuery();

        $result = $query->getResult();
        shuffle($result);

        return array_slice($result, 0, $count);
    }

    public function getChangeLog() {

        $logs = [];
        /** @var Model\Entry $entry */
        foreach($this->findBy(['published' => 1], ['publishedAt' => 'desc']) as $entry) {
            $logs[$entry->getYear()][$entry->getPublishedAt('short')][] = [
                'url' => $entry->getUrl(),
                'title' => $entry->getTitle(),
                'message' => 'Published.'
            ];
        }

        $qb = $this->getEntityManager()->getRepository(Model\EntryLog::class)->createQueryBuilder('el');
        $query = $qb
            ->orderBy('el.createdAt', 'DESC');

        foreach($query->getQuery()->iterate() as $row) {
            /** @var Model\EntryLog $log */
            $log = array_pop($row);
            $entry = $log->getEntry();
            if($entry->getPublishedAt() > new \DateTime('now')) continue;
            $logs[$log->getCreatedAt()->format('Y')][$log->getCreatedAt()->format('M j, Y')][] = [
                'url' => $entry->getUrl(),
                'title' => $entry->getTitle(),
                'message' => $log->getMessage()
            ];
        }

        return $logs;
    }

    /**
     * @param bool $all
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function getPublished($all = false) {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->where($qb->expr()->eq('e.published', 1))
            ->addOrderBy('e.publishedAt', 'asc');

        if($all == false) {
            $lookback = date('Y-m-01');
            $query->andWhere("date(e.publishedAt) > date('$lookback')");
        }

        return $query->getQuery()->iterate();
    }

    /**
     * @param null $type
     * @return Model\Entry
     */
    public function getLastEntry($type = null) {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->orderBy('e.publishedAt', 'DESC')
            ->setMaxResults(1);

        if($type)
            $query->where($qb->expr()->eq('e.queue', $type));

        return array_pop($query->getQuery()->getResult());
    }

    public function getPublishedAt($marker) {
        if(!is_string($marker))
            $marker = $marker->format('Y-m-d');
        $qb = $this->createQueryBuilder('e');

        $query = $qb
            ->where($qb->expr()->eq('date(e.publishedAt)', "date('{$marker}')"))
            ->orderBy('e.publishedAt', 'DESC');

        $results = $query->getQuery()->getResult();

        return count($results) > 1 ? $results : array_pop($results);
    }

}