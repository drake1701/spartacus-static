<?php
/**
 * @author     Dennis Rogers <dennis@drogers.net>
 * @address    www.drogers.net
 * 
 * Verify link and get thumbnail url for posts without a thumb url.
 */
    
    
$debug = isset($argv[1]) && !empty($argv[1]);
$db = new PDO('sqlite:'.dirname(__FILE__).'/index.sqlite');

$postsCount = array_pop($db->query('SELECT count(*) FROM posts WHERE `thumb` IS NULL;')->fetch());

$sql = $db->prepare("SELECT * FROM `posts` WHERE `thumb` IS NULL ORDER BY `id` DESC LIMIT 1000;");

$sql->execute();
$posts = $sql->fetchAll();

echo "-- Start Thumbs Build --\n";
date_default_timezone_set('EST');
echo date(DATE_RFC2822) . "\n";
echo "Current count: $postsCount\n";
$stats = array(
    'New Cache' => 0,
    'No Thumb' => 0,
    'Bad Url' => 0,
    'No Image' => 0
);
$i = 0;
foreach($posts as $post){
    $fetchResults = array();
    if ($debug)
        echo $post['url']."\n";
    $fetchResults = doCurl('http://fetch.spartacuswallpaper.com/fetch.php?json=1&url='.$post['url']);
    $fetchData = json_decode($fetchResults);
    if(count($fetchData)) {
        $thumb = array_pop($fetchData);
        if(isset($thumb->error)) {
            $thumb = $thumb->msg;
        } else {
            $thumb = $thumb->url;
        }
    } else {
        $thumb = $fetchResults;
    }
    if ($debug)
        echo $thumb."\n";
    if(substr($thumb, 0, 4) != 'http') {
        $stats['Bad Url'] += 1;
        $sql = $db->prepare('UPDATE `posts` SET `bad` = 1, `thumb` = "fail" WHERE `id` = :id;');
        $sql->bindValue(':id',  $post['id']);    
        $sql->execute();
        continue;
    } else {
        $thumb = preg_replace("#(www.upix.me/)i/v/\?q=(.+?)\.(.+?.jpg)#", "\\1u/n/\\2/\\3", $thumb);
        $headers = get_headers($thumb);
        if(empty($headers) || preg_match("#404 Not Found#", $headers[0]) || (isset($headers[1]) && preg_match("#404 Not Found#", $headers[1]))) {
            $stats['Bad Url'] += 1;
            $sql = $db->prepare('UPDATE `posts` SET `bad` = 1, `thumb` = :thumb WHERE `id` = :id;');
            $sql->bindValue(':thumb', $thumb);
            $sql->bindValue(':id',  $post['id']);    
            $sql->execute();
            continue;
        }
        $image = doCurl($thumb);
        if(substr($image, 0, 4) == 'http') {
            $image = doCurl($image);
        }
        if($image == '') {
            $stats['No Image'] += 1;
            $sql = $db->prepare('UPDATE `posts` SET `bad` = 1, `thumb` = :thumb WHERE `id` = :id;');
            $sql->bindValue(':thumb', $thumb);
            $sql->bindValue(':id',  $post['id']);  
            $sql->execute();
            continue;
        }
        $filename = dirname(__FILE__) . '/cache/' . $post['id'];
        file_put_contents($filename, $image);
        exec('convert '.($debug?'':' -verbose').' -quality 75 -strip -channel RGB -resize "320>" '.$filename . ' ' . $filename );

        $stats['New Cache'] += 1;
        $sql = $db->prepare('UPDATE `posts` SET `cached` = 1, `bad` = 0, `thumb` = :thumb WHERE `id` = :id;');
        $sql->bindValue(':thumb', $thumb);
        $sql->bindValue(':id',  $post['id']);  
        $sql->execute();
    }
}
print_r($stats);
$postsCount = array_pop($db->query('SELECT count(*) FROM posts WHERE `thumb` IS NULL ;')->fetch());
echo "Current count: $postsCount\n";

function doCurl($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
    return curl_exec($ch);
}
?>