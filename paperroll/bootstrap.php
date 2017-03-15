<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

error_reporting(E_WARNING);
session_start();
require_once __DIR__ . "/vendor/autoload.php";

const BASEDIR = __DIR__;

\Paperroll\Helper\Registry::set('logger', \Paperroll\Helper\Logger::init());
\Paperroll\Helper\Registry::set('entityManager', \Paperroll\Helper\Entity::init());
