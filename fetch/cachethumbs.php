<?php
/**
 * @author     Dennis Rogers <dennis@drogers.net>
 * @address    www.drogers.net
 *
 * Fetch, download and resize thumb images.
 */

    

$debug = isset($argv[2]) && !empty($argv[2]);
$urlId = false;
$db = new PDO('sqlite:'.dirname(__FILE__).'/index.sqlite');
if(php_sapi_name() == "cli" && !empty($argv[1])){
    $sql = $db->prepare("SELECT * FROM `posts` WHERE `id` = :id;");
    $sql->bindValue(':id', $argv[1]);
} else if (isset($_GET['id']) && !empty($_GET['id'])) {
    $urlId = true;
    $sql = $db->prepare("SELECT * FROM `posts` WHERE `id` = :id;");
    $sql->bindValue(':id', $_GET['id']);
} else {
    $sql = $db->prepare("SELECT * FROM `posts` WHERE `thumb` IS NOT NULL AND `bad` != 1 AND `cached` IS NULL ORDER BY `id` DESC LIMIT 1000;");
}
echo "-- Start Thumbs Cache --\n";
date_default_timezone_set('EST');
echo date(DATE_RFC2822) . "\n";

$sql->execute();
$posts = $sql->fetchAll();
$stats = array(
    'New Cache' => 0,
    'Already Cached' => 0,
    'No Thumb' => 0,
    'Bad Url' => 0,
    'No Image' => 0,
    'Too Small' => 0
);
$i = 0;
foreach($posts as $post){
    $thumb = $post['thumb'];
    if($debug)
        echo "\n".$post['id'].' - '.$thumb."\n";
    if(file_exists(dirname(__FILE__) . '/cache/' . $post['id'])){
        if($debug)
            echo "cached\n";
        $stats['Already Cached'] += 1;
        $sql = $db->prepare('UPDATE `posts` SET `cached` = 1 WHERE `id` = :id;');
        $sql->bindValue(':id',  $post['id']);
        $sql->execute();
        continue;
    }
    if($thumb == '' || $thumb == 'fail') {
        $fetchResults = doCurl('http://fetch.spartacuswallpaper.com/fetch.php?json=1&url='.$post['url']);
        $fetchResults = json_decode($fetchResults);
        $thumb = array_pop($fetchResults);
        $thumb = $thumb->url;
        if(substr($thumb, 0, 4) != 'http') {
            if($debug)
                echo "no thumb set or found\n";
            $stats['No Thumb'] += 1;
            $sql = $db->prepare('UPDATE `posts` SET `bad` = 1, `thumb` = "fail" WHERE `id` = :id;');
            $sql->bindValue(':id',  $post['id']);    
            $sql->execute();
            if($urlId) { 
                header("HTTP/1.0 404 Not Found");
                die();
            }
            continue;
        }
    }
    $thumb = preg_replace("#(www.upix.me/)i/v/\?q=(.+?)\.(.+?.jpg)#", "\\1u/n/\\2/\\3", $thumb);
    $headers = get_headers($thumb);
    if(empty($headers) || preg_match("#404 Not Found#", $headers[0]) || (isset($headers[1]) && preg_match("#404 Not Found#", $headers[1]))) {
        if($debug)
            echo "bad thumb url\n";
        $stats['Bad Url'] += 1;
        $sql = $db->prepare('UPDATE `posts` SET `bad` = 1 WHERE `id` = :id;');
        $sql->bindValue(':id', $post['id']);    
        $sql->execute();
        if($urlId) { 
            header("HTTP/1.0 404 Not Found");
            die();
        }
        continue;
    }
    $image = doCurl($thumb);
    if($image == '') {
        if($debug)
            echo "no image found\n";
        $stats['No Image'] += 1;
        $sql = $db->prepare('UPDATE `posts` SET `bad` = 1 WHERE `id` = :id;');
        $sql->bindValue(':id', $post['id']);    
        $sql->execute();
        if($urlId) { 
            header("HTTP/1.0 404 Not Found");
            die();
        }
        continue;
    }
    if($debug)
        "new file\n";
    $stats['New Cache'] += 1;
    $filename = dirname(__FILE__) . '/cache/' . $post['id'];
    file_put_contents($filename, $image);
    
    //check dimensions
    $info = getimagesize($filename);
    if($info[0] > 0 && $info[1] > 0 && ($info[0] < 1000 && $info[1] < 1000)){
        if($debug) echo "No dimensions or image too small " . $info[0] . 'x' . $info[1] . ".\n";
        $sql = $db->prepare('UPDATE `posts` SET `cached` = 1, `reject` = 1, `thumb` = :thumb WHERE `id` = :id;');
        $stats['Too Small'] += 1;
        unlink($filename);
    } else {
        $sql = $db->prepare('UPDATE `posts` SET `cached` = 1, `thumb` = :thumb WHERE `id` = :id;');
        exec('convert '.($debug?'':' -verbose').' -quality 75 -strip -channel RGB -resize "320>" '.$filename . ' ' . $filename ); 
    }
    $sql->bindValue(':thumb',  $thumb);
    $sql->bindValue(':id',  $post['id']);
    $sql->execute();
}
if($debug)
    echo dirname(__FILE__) . '/cache/' . $post['id'];
    

$stats['Remaining'] = array_pop($db->query('SELECT count(*) FROM posts WHERE `thumb` IS NOT NULL AND `bad` != 1 AND `cached` IS NULL;')->fetch());

print_r($stats);

function doCurl($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
    return curl_exec($ch);
}
?>