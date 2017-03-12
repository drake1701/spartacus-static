<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;

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
}