<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Helper;

class Registry
{
    private static $_objects = [];

    public static function set($name, $object) {
        self::$_objects[$name] = $object;
    }

    public static function &get($name) {

        if (!isset(self::$_objects[$name])) {
            throw new \Exception(sprintf('Requested "%s" instance is not in the registry', $name));
        }

        return self::$_objects[$name];
    }
}