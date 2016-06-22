<?php
/**
 * Script to cache images 
 *
 * @author      Dennis Rogers <dennis@drogers.net>
 * @address     www.drogers.net
 */


require_once dirname(dirname(dirname(__FILE__))).'/app.php';

// If script is called directly, give debug info
$debug = isset($_SERVER['HTTP_REFERER']) ? false : true;
if($debug) {
    error_reporting(E_ALL);
    ini_set('show_errors', 1);
}
die('foo');

try {
    // parse requested string
    $requestedFile = $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim($requestedFile, '/'));
    
    // must be five parts
    if(count($pathParts) != 3)
        throw new Exception(print_r($pathParts, 1));
    
    // get filename and type
    $fileParts = pathinfo(basename($requestedFile));
    $filename = transcode($fileParts['filename']) . '.' . $fileParts['extension'];
    $kind = transcode($pathParts[3]);
    
    // get width
    $width = $pathParts[2];
    
    $originalFile = $base_dir . 'gallery/' . $kind . '/' . $filename;
    
    if(!file_exists($originalFile))
        throw new Exception("Not exists $originalFile");

    $im = new Imagick($originalFile);
    
    $im->resizeImage ( $width, 0 , Imagick::FILTER_QUADRATIC , 1 ); 
            
    $im->normalizeImage();
    $im->unsharpMaskImage(2 , .9 , 1 , 0.05); 
            
    $im->setImageFormat("jpg"); 
    $im->setCompressionQuality(75);
    
    $cacheFile = $base_dir . implode('/', $pathParts);
    
    if(!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0775, 1);
    }
    
    $im->writeImage($cacheFile); 
    
    header('Content-Type: image/jpg');
    echo $im->getImageBlob();

} Catch (Exception $e) {
    var_dump($e);
    if($debug)
        var_dump($e);
    else 
        header('Location: /gallery/cache/placeholder.png');
        
}




