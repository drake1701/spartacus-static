<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Helper;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class Entity {

    public static function init() {

        $devMode = strpos(BASEDIR, 'development');
        $config = Setup::createAnnotationMetadataConfiguration(
            [ BASEDIR . "/app/Paperroll/Model" ],
            $devMode
        );

        // database configuration parameters
        $conn = [
            'driver' => 'pdo_sqlite',
            'path'   => BASEDIR . '/var/db/spartacus'
        ];

        return EntityManager::create( $conn, $config );
    }

}