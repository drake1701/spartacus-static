<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;

use Paperroll\Model\EntryTag;
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

    /**
     * @param null $tagId
     * @param array $excludeIds
     * @param int $count
     * @return array
     */
    public function getRandom($tagId = null, $excludeIds = [], $count = 1) {
        $qb = $this->getEntityManager()->getRepository(\Paperroll\Model\Entry::class)->createQueryBuilder('e');
        $query = $qb
            ->join(EntryTag::class, 'et', 'WITH', 'et.entryId = e.id')
            ->join(Model\Image::class, 'i', 'WITH', 'i.entryId = e.id')
            ->join(Model\ImageKind::class, 'ik', 'WITH', 'ik.id = i.kind AND ik.mobile = 1')
            ->where($qb->expr()->eq('et.tagId', $tagId));

        if(count($excludeIds)) {
            $query->andWhere($qb->expr()->notIn('e.id', $excludeIds));
        }

        $result = $query->getQuery()->getResult();
        shuffle($result);

        return $count > 1 ? array_slice($result, 0, $count) : array_pop($result);
    }

}