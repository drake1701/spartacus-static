<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;

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

}