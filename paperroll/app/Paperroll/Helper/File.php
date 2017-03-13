<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Helper;

class File {

    public static function siteDir() {
        return BASEDIR . '/_site';
    }

    public static function baseUrl() {
        return strpos(__DIR__, 'development') ? 'http://dev.spartacuswallpaper.com/' : 'http://www.spartacuswallpaper.com/';
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

    public static function copy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0775);
        @chmod($dst, 0775);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( !is_dir($src . '/' . $file) ) {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Write file, create directory if needed
     * @param $filename
     * @param $content
     */
    public static function writeFile($filename, $content ) {
        $dir = dirname($filename);
        if(!is_dir($dir)) {
            mkdir($dir, 0775, true);
            chmod($dir, 0775);
        }
        file_put_contents($filename, $content);
    }

    /**
     * Read file
     * @param $filename
     * @return string
     */
    public static function readFile($filename) {
        return @file_get_contents($filename);
    }

    /**
     * Write site page, both direct and folder version
     * @param $slug
     * @param $content
     */
    public static function writePage($slug, $content) {
        $slug = str_replace(".html", "", $slug);
        $logger = Registry::get('logger');
        $logger->debug("Write ".self::baseUrl()."$slug/");

        self::writeFile(self::siteDir()."/$slug.html", $content);
        self::writeFile(self::siteDir()."/$slug/index.html", $content);
    }

    public static function getCacheUrl($image, $size) {

        $filename = str_replace('.jpg', '', basename($image));
        $kind = basename(dirname($image));

        $encoded = self::transcode($kind . '/' . $filename);

        $url = self::baseUrl() . 'gallery/cache/' . $size . '/' . $encoded . '.jpg';

        return $url;
    }

    public static function codeToName($code) {
        if(preg_match("#-[ivxlm]*\$#", $code)){
            $parts = explode("-", $code);
            $roman = strtoupper(array_pop($parts));
            array_push($parts, $roman);
            $code = implode("-", $parts);
        }
        return trim(ucwords(preg_replace("#(-|_)#", " ", $code)));
    }

    public static function transcode($string) {
        $key = ['0' => 'a', '-' => 'y', '_' => '4', 'a' => '0', 'b' => 'c', 'c' => 'b', 'd' => '3', 'e' => '6', 'f' => 'n', 'g' => 'o', 'h' => 'm', 'i' => 'j', 'j' => 'i', 'k' => 'q', 'l' => '9', 'm' => 'h', 'n' => 'f', 'o' => 'g', 'p' => 't', 'q' => 'k', 'r' => 'w', 's' => '8', 't' => 'p', 'u' => '1', 'v' => '2', 'w' => 'r', 'x' => 'z', 'y' => '-', 'z' => 'x', '1' => 'u', '2' => 'v', '3' => 'd', '4' => '_', '5' => '7', '6' => 'e', '7' => '5', '8' => 's', '9' => 'l', '/' => '/'];

        $in = str_split($string);
        $out = [];
        foreach ($in as $v) {
            $out[] = $key[$v];
        }

        return implode('', $out);
    }

    public static function bytesToSize($bytes, $precision = 2) {

        $kilobyte = 1024;
        $megabyte = $kilobyte * 1024;
        $gigabyte = $megabyte * 1024;
        $terabyte = $gigabyte * 1024;

        if (($bytes >= 0) && ($bytes < $kilobyte)) {
            return $bytes . ' B';

        } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
            return round($bytes / $kilobyte, $precision) . ' KB';

        } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
            return round($bytes / $megabyte, $precision) . ' MB';

        } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
            return round($bytes / $gigabyte, $precision) . ' GB';

        } elseif ($bytes >= $terabyte) {
            return round($bytes / $terabyte, $precision) . ' TB';
        } else {
            return $bytes . ' B';
        }
    }

}