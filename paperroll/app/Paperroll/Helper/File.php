<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Helper;

class File {

    public static function siteDir()
    {
        return BASEDIR . '/../_site';
    }

    public static function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public static function recurseCopy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0775);
        @chmod($dst, 0775);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    self::recurseCopy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public static function writeFile( $filename, $content ) {
        $dir = dirname($filename);
        if(!is_dir($dir)) {
            mkdir($dir, 0775, true);
            chmod($dir, 0775);
        }
        file_put_contents($filename, $content);
    }

    public static function readFile($filename) {
        return file_get_contents($filename);
    }

}