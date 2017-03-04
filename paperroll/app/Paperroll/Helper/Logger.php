<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Helper;

use Monolog\Handler\StreamHandler;

class Logger {

    /**
     * @return \Monolog\Logger
     */
    static public function init() {

        $log = new \Monolog\Logger('paper');
        $log->pushHandler(new StreamHandler('var/log/system.log'));
        $log->pushHandler(new StreamHandler('var/log/error.log', LOG_ERR));
        return $log;
    }

}