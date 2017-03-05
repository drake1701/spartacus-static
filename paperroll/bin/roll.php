<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

require_once dirname(__DIR__) . '/bootstrap.php';

try {
    $class = "Paperroll\\Command\\" . ucwords($argv[1]);
    /** @var \Paperroll\Command\Generic $command */
    $command = new $class($argv);
    $command->execute();
} Catch (\Exception $e) {
    echo $e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
}