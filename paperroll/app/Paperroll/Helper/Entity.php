<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Helper;


use Doctrine\Common\Proxy\Autoloader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class Entity {

    public static function init() {

        $devMode = strpos(BASEDIR, 'development');

        $proxyDir = BASEDIR . '/var/db/proxies';
        $proxyNS = 'Proxies';

        $cache = new \Doctrine\Common\Cache\ArrayCache;

        $config = new Configuration();
        $config->setMetadataCacheImpl($cache);
        $driverImpl = $config->newDefaultAnnotationDriver(BASEDIR . "/app/Paperroll/Model");
        $config->setMetadataDriverImpl($driverImpl);
        $config->setQueryCacheImpl($cache);
        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace($proxyNS);
        $config->setAutoGenerateProxyClasses(true);

        Autoloader::register($proxyDir, $proxyNS);

        // database configuration parameters
        $conn = [
            'driver' => 'pdo_sqlite',
            'path'   => BASEDIR . '/var/db/spartacus'
        ];

        return EntityManager::create( $conn, $config );
    }

}