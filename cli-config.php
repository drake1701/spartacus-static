<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

require_once "bootstrap.php";

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$devMode = strpos(BASEDIR, 'development');
$config = Setup::createAnnotationMetadataConfiguration(
    [ BASEDIR . "/app/Paperroll/Model" ],
    $devMode
);

// database configuration parameters
$conn = [
    'driver' => 'pdo_sqlite',
    'path'   => BASEDIR . '/db/spartacus'
];

$entityManager = EntityManager::create($conn, $config);

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);