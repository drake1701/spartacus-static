<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model;


use Paperroll\Helper\Registry;
use Paperroll\Model\Repository;

class Queue
{
    const NORMAL = 1;
    const CALENDAR = 2;

    /** @var  int */
    private $_type;

    /** @var  Repository\Entry */
    private $_entryRepo;

    /** @var \Paperroll\Model\Entry  */
    private $_lastPublished;

    /** @var \DateTime */
    private $_next;
    private $logger;

    /**
     * Queue constructor.
     * @param $type
     */
    public function __construct($type) {
        $this->_type = $type;
        $this->_entryRepo = Registry::get('entityManager')->getRepository(Entry::class);
        $this->_lastPublished = $this->_entryRepo->getLastEntry($type);
        $this->_next = $this->_lastPublished->getPublishedAt();
        $this->logger = Registry::get('logger');
    }

    public function reset() {
        $this->_lastPublished = $this->_entryRepo->getLastPublishedEntry($this->_type);
        $this->_next = $this->_lastPublished->getPublishedAt();
    }

    public function getNext() {
        $date = $this->_next;
        switch($this->_type){
            case self::NORMAL:
                do {
                    $last_dow = $date->format("w");
                    if ($last_dow == 1) {
                        $date->add(new \DateInterval("P3D"));
                    } else {
                        $date->add(new \DateInterval("P2D"));
                    }
                } while($date->format("d") == 1);
                break;
            case self::CALENDAR:
                $date->add(new \DateInterval("P1M"));
                break;
            default:
                break;
        }
        $this->_next = $date;
        return $this->_next;
    }


}