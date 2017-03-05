<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model\Repository;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use Paperroll\Helper\Logger;

class Generic extends EntityRepository
{
    /** @var \Monolog\Logger  */
    protected $logger;

    public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $emConfig = $this->getEntityManager()->getConfiguration();
        $emConfig->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Sqlite\Year');
        $emConfig->addCustomDatetimeFunction('DATE', 'DoctrineExtensions\Query\Sqlite\Date');

        $this->logger = Logger::init();
    }

}