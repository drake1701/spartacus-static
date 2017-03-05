<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;


use Paperroll\Model\EntryTag;
use Paperroll\Model\Image;
use Paperroll\Model;

class Tag extends Generic
{
    public function getTopTen() {
        $qb = $this->createQueryBuilder('t');
        $query = $qb
            ->where('t.name = 1')
            ->orderBy('t.count', 'desc')
            ->setMaxResults(10)
            ->getQuery();

        return $query->getResult();
    }

    public function getRandom($tagId) {
        $qb = $this->getEntityManager()->getRepository(\Paperroll\Model\Entry::class)->createQueryBuilder('e');
        $query = $qb
            ->join(EntryTag::class, 'et', 'WITH', 'et.entryId = e.id')
            ->join(Image::class, 'i', 'WITH', 'i.entryId = e.id')
            ->join(Model\ImageKind::class, 'ik', 'WITH', 'ik.id = i.kind AND ik.mobile = 1')
            ->where($qb->expr()->eq('et.tagId', $tagId))
            ->getQuery();

        $entries = $query->getResult();

        return $entries[array_rand($entries)];
    }

}