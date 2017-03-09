<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

require_once __DIR__ . "/vendor/autoload.php";

const BASEDIR = __DIR__;

\Paperroll\Helper\Registry::set('logger', new \Paperroll\Helper\Logger());
\Paperroll\Helper\Registry::set('entityManager', \Paperroll\Helper\Entity::init());
