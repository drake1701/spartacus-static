<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Paperroll\Helper\File;
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
     * @param string $slugs
     * @return ArrayCollection
     */
    public function buildArray($slugs) {
        $tags = new ArrayCollection();
        $slugs = explode(',', $slugs);
        foreach($slugs as $slug) {
            $slug = trim($slug);
            if(strlen($slug) < 2) continue;
            $tag = $this->findBy(['slug' => $slug]);
            if(count($tag)) {
                $tags->add(array_pop($tag));
            } else {
                $tag = new Model\Tag();
                $tag->setSlug($slug);
                $tag->setTitle(File::codeToName($slug));
                $this->getEntityManager()->persist($tag);
                $tags->add($tag);
            }
        }
        return $tags;
    }
}