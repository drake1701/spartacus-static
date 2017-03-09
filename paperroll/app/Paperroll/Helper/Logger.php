<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Helper;

use Monolog\Handler\StreamHandler;

class Logger extends \Monolog\Logger {

    public function __construct($name = 'Paper', $handlers = [], $processors = [])
    {
        parent::__construct($name, $handlers, $processors);
        $this->pushHandler(new StreamHandler('var/log/system.log'));
        $this->pushHandler(new StreamHandler('var/log/error.log', LOG_ERR));
    }

    public function addRecord($level, $message, array $context = [])
    {
        if (php_sapi_name() == "cli") {
            echo $message . "\n";
        }
        return parent::addRecord($level, $message, $context);
    }

}