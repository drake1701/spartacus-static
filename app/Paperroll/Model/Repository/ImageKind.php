<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;

use Paperroll\Model;

class ImageKind extends Generic
{

    public function getVisibleKinds()
    {
        $qb = $this->createQueryBuilder('k');
        $kindsResult = $qb
            ->where($qb->expr()->isNotNull('k.position'))
            ->where('k.exclude = 0')
            ->orderBy('k.mobile')
            ->orderBy('k.position')
            ->getQuery()
            ->getResult();
        return $kindsResult;
    }

    protected function _getEntriesQuery($kind) {
        $qb = $this->getEntityManager()->getRepository(Model\Entry::class)->createQueryBuilder('e');
        $query = $qb
            ->join(Model\Image::class, 'i', 'WITH', 'i.entryId = e.id')
            ->join(Model\ImageKind::class, 'k', 'WITH', 'i.kind = k.id')
            ->where($qb->expr()->eq('k.id', $kind->getId()))
            ->orderBy('e.publishedAt', 'DESC');
        return $query;
    }

    /**
     * @param \Paperroll\Model\ImageKind $kind
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function getEntries($kind) {
        return $this->_getEntriesQuery($kind)->getQuery()->iterate();
    }

    public function getCount($kind) {
        return $this->_getEntriesQuery($kind)->select('count(e)')->getQuery()->getSingleScalarResult();
    }

    public function getYears($kind) {
        $years = [];
        foreach($this->getEntries($kind) as $row) {
            $entry = array_pop($row);
            $years[$entry->getYear()] = [];
        }
        return $years;
    }

    public function getIds() {
        $kinds = $this->findAll();
        $mapped = [];
        foreach($kinds as $kind) {
            $mapped[$kind->getPath()] = $kind->getId();
        }
        return $mapped;
    }

}