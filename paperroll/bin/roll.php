<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

require_once dirname(__DIR__) . '/bootstrap.php';

ini_set('memory_limit', '521M');

try {
    $time = microtime(1);
    $class = "Paperroll\\Command\\" . ucwords($argv[1]);
    /** @var \Paperroll\Command\Generic $command */
    $command = new $class($argv);
    $command->execute();
    $time = microtime(1) - $time;
    echo "$time script execution\n";
} Catch (\Exception $e) {
    echo $e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
}