<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;

use Doctrine\ORM\Query\Expr;
use Paperroll\Model\Image;
use Paperroll\Model\ImageKind;
use Paperroll\Model\Tag;

class Entry extends Generic
{
    public function getLastPublishedEntry() {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->where($qb->expr()->isNotNull('e.published'))
            ->orderBy('e.publishedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return array_pop($query->getResult());
    }

    public function rePublish(\Paperroll\Model\Entry $entry) {
        $this->logger->debug("Mark {$entry->getTitle()} ({$entry->getId()}) to be republished.");
        $entry->setPublished(null);
    }

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

    public function getLastPublishedCalendar()
    {
        $qb = $this->createQueryBuilder('e');
        $query = $qb
            ->addSelect('k.path as kind')
            ->join(Image::class, 'i', Expr\Join::WITH, 'i.entryId = e.id')
            ->join(ImageKind::class, 'k', Expr\Join::WITH, 'i.kind = k.id')
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

    public function getTopTen() {

        /** @var \Paperroll\Model\Repository\Tag $tags */
        $tags = $this->getEntityManager()->getRepository(Tag::class);
        $topTen = $tags->getTopTen();

        $entries = [];
        /** @var Tag $tag */
        foreach($topTen as $tag) {
            $entries[] = $tags->getRandom($tag->getId());
        }
        return $entries;
    }

}