<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Helper;

class Entry {

    protected $entityManger;

    protected $class = \Paperroll\Model\Entry::class;

    /**
     * Entry constructor.
     */
    public function __construct() {
        $this->entityManger = Entity::init();
        $this->logger = Logger::init();
    }

    public function getLastPublishedEntry() {
        $qb = $this->entityManger->getRepository($this->class)->createQueryBuilder('e');
        $result = $qb
            ->where($qb->expr()->isNotNull('e.published'))
            ->orderBy('e.publishedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return array_pop($result);
    }

    public function rePublish(\Paperroll\Model\Entry $entry) {
        $this->logger->debug("Mark {$entry->getTitle()} ({$entry->getId()}) to be republished.");
        $entry->setPublished(null);
    }
}