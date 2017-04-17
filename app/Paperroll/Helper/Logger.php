<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Helper;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class Logger {

    /**
     * @return \Monolog\Logger
     */
    static public function init() {

        $log = new \Monolog\Logger('paper');
        $formatter = new LineFormatter(null, null, false, true);

        $handler = new StreamHandler('php://stdout');
        $handler->setFormatter($formatter);
        $log->pushHandler($handler);

        $handler = new StreamHandler(BASEDIR . '/var/log/system.log');
        $handler->setFormatter($formatter);
        $log->pushHandler($handler);

        $handler = new StreamHandler(BASEDIR . '/var/log/error.log', \Monolog\Logger::ERROR);
        $handler->setFormatter($formatter);
        $log->pushHandler($handler);
        return $log;
    }

}