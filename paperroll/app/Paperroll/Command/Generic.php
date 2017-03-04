<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Command;


use Paperroll\Helper;

class Generic {

    /** @var \Monolog\Logger  */
    protected $logger;

    /** @var  array */
    protected $args;

    /** @var bool  */
    protected $dev = false;

    /**
     * Generate constructor.
     *
     * @param array $argv
     */
    public function __construct($argv = []) {

        $this->dev = strpos(__DIR__, 'development');

        $this->logger = Helper\Logger::init();

        $this->entityManger = Helper\Entity::init();

        array_shift($argv);
        array_shift($argv);
        $this->args = array_combine($argv, $argv);

    }

    protected function getArg($key) {
        return !empty($this->args[$key]);
    }

    public function execute() {}

    public function debug($msg) {
        $this->logger->debug($msg);
        echo "$msg\n";
    }

    public function error($msg) {
        $this->logger->error($msg);
        echo "$msg\n";
    }

}