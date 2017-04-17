<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */
require_once __DIR__ . '/../bootstrap.php';

$router = new \Paperroll\Admin\Router();
$router->execute();