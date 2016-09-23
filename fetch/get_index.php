<?php

$debug = isset($argv[1]) && !empty($argv[1]);
    
$db = new PDO('sqlite:'.dirname(__FILE__).'/index.sqlite');
$db->query('CREATE TABLE IF NOT EXISTS `posts` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `name` TEXT, `url` TEXT NOT NULL UNIQUE, "thumb" TEXT, "bad" BOOL DEFAULT 0, "reject" BOOL DEFAULT 0)');

echo "-- Start Get Index --\n";
date_default_timezone_set('EST');
echo date(DATE_RFC2822) . "\n";
$postsCount = array_pop($db->query('SELECT count(*) FROM posts;')->fetch());
echo "Current count: $postsCount\n";

$superiorpics = array(
    "http://www.superiorpics.com/c/Actresses_A_F/",
    "http://www.superiorpics.com/c/Actresses_G_K/",
    "http://www.superiorpics.com/c/Actresses_L_O/",
    "http://www.superiorpics.com/c/Actresses_P_Z/",
    "http://www.superiorpics.com/c/Models_Athletes/",
    "http://www.superiorpics.com/c/Singers_Musicians/"
);

foreach($superiorpics as $category){
    $i = 1;
    $url = $category;
    if($debug)
    	echo $url . "\n";
    
    do {
        if($i > 1)
            $url = $category . "index_" . $i . ".html";
            
        if($debug)
        	echo "page ".$i."\n";

        $i++;
        $headers = get_headers($url, 1);
        if(preg_match("#404 Not Found#", $headers[0]))  {
            echo 'ERROR: No posts found for '.$url."\n";
            break;
        }

        $page = doCurl($url);
        $page = str_replace("\n", " ", $page);
        preg_match_all('#<div class="forum-www-table-row-postlist">.+?<div class="clear">#', $page, $posts);
        $posts = array_pop($posts);
        foreach($posts as $post){
            preg_match("#>([^<]+?)<#", $post, $title);
            $title = array_pop($title);
            preg_match('#"(http://forums.superiorpics.com/ubbthreads/ubbthreads.php/topics/.+?)\\##', $post, $url);
            $url = array_pop($url);
            
            $sql = $db->prepare('INSERT INTO `posts` (`name`, `url`) VALUES (:name, :url);');
            $sql->bindValue(':name', $title);
            $sql->bindValue(':url', $url);    
            $sql->execute();
            if($debug)
            	echo $title."\n";
            if($i > 2 && $sql->rowCount() == 0) break 2;
        }
        sleep(1);
    } while(1);
}

echo 'superiorpics.com: ' . (array_pop($db->query('SELECT count(*) FROM posts;')->fetch()) - $postsCount) . "\n";
$postsCount = array_pop($db->query('SELECT count(*) FROM posts;')->fetch());

$i = 1;
$base = "http://celebutopia.net/forums/forumdisplay.php?f=15&order=desc";

if($debug)
    echo $base . "\n";

do {
    if($debug)
    	echo "page " . $i . "\n";
    $url = $base . "&page=" . $i++;
    $listPage = doCurl($url);
    preg_match_all('#<a[^>]+?href="(showthread[^"]+?)"[^>]+?>([^<]+?)</a>#s', $listPage, $posts);
    if(count($posts[0]) == 0)  {
        echo 'ERROR: No posts found for '.$url."\n";
        break;
    }
    foreach($posts[0] as $key => $post) {
        $sql = $db->prepare('INSERT INTO `posts` (`name`, `url`) VALUES (:name, :url);');
        $sql->bindValue(':name', $posts[2][$key]);
        $sql->bindValue(':url', "http://celebutopia.net/forums/" . $posts[1][$key]);    
        $sql->execute();
        if($debug)
        	echo $posts[2][$key]."\n";
        if($i > 2 && $sql->rowCount() == 0) break 2;
    }
} while(1);

echo 'celebutopia.net: ' . (array_pop($db->query('SELECT count(*) FROM posts;')->fetch()) - $postsCount) . "\n";
$postsCount = array_pop($db->query('SELECT count(*) FROM posts;')->fetch());

$base = "http://www.hawtcelebs.com/";
$i = 0;
do {
    $url = $base;
    if(++$i > 1) $url = $base . "page/" . $i;
    if($debug)
    	echo $url . "\n";
    $listPage = doCurl($url);
    preg_match_all('#<h1><a title="Permanent Link to ([^"]+?)" href="([^"]+?)"#s', $listPage, $posts);
    if(count($posts[0]) == 0) {
        echo 'ERROR: No posts found for '.$url."\n";
        break;
    }
    foreach($posts[0] as $key => $post) {
        $sql = $db->prepare('INSERT INTO `posts` (`name`, `url`) VALUES (:name, :url);');
        $sql->bindValue(':name', html_entity_decode($posts[1][$key]));
        $sql->bindValue(':url',  $posts[2][$key]);    
        $sql->execute();
        if($debug)
        	echo html_entity_decode($posts[1][$key])."\n";
        if($i > 1 && $sql->rowCount() == 0) break 2;
    }
} while(1);

echo 'hawtcelebs.com: ' . (array_pop($db->query('SELECT count(*) FROM posts;')->fetch()) - $postsCount) . "\n";
$postsCount = array_pop($db->query('SELECT count(*) FROM posts;')->fetch());

foreach(array("http://www.gotceleb.com/", "http://www.fabzz.com/") as $base) {
    $i = 0;
    do {
        $url = $base;
        if(++$i > 1) $url = $base . "page/" . $i;
        if($debug)
        	echo $url . "\n";
        $listPage = doCurl($url);
        preg_match_all('#<h2 class="post-title".+?<a href="([^"]+?)"[^<]+?title="([^"]+?)"#s', $listPage, $posts);
        if(count($posts[0]) == 0) {
            echo 'ERROR: No posts found for '.$url."\n";
            break;
        }
        foreach($posts[0] as $key => $post) {
            $sql = $db->prepare('INSERT INTO `posts` (`name`, `url`) VALUES (:name, :url);');
            $sql->bindValue(':name', html_entity_decode($posts[2][$key]));
            $sql->bindValue(':url',  $posts[1][$key]);    
            $sql->execute();
            if($debug)
            	echo html_entity_decode($posts[2][$key])."\n";
            if($i > 2 && $sql->rowCount() == 0) break 2;
        }
    } while(1);
}

echo 'gotceleb.com: ' . (array_pop($db->query('SELECT count(*) FROM posts;')->fetch()) - $postsCount) . "\n";
$postsCount = array_pop($db->query('SELECT count(*) FROM posts;')->fetch());

$base = "http://www.carreck.com/pictures/";
$i = 0;
do {
    $url = $base;
    if(++$i > 1) $url = $base . "page/" . $i;
    if($debug)
    	echo $url . "\n";
    $listPage = doCurl($url);
    preg_match_all('#<h2 class="posttitle".+?<a href="([^"]+?)"[^<]+?title="Permanent Link to ([^"]+?)"#s', $listPage, $posts);
    if(count($posts[0]) == 0) {
        echo 'ERROR: No posts found for '.$url."\n";
        break;
    }
    foreach($posts[0] as $key => $post) {
        $sql = $db->prepare('INSERT INTO `posts` (`name`, `url`) VALUES (:name, :url);');
        $title = html_entity_decode($posts[2][$key]);
        $title = preg_replace("/#(.+?) /", '\1 ', $title);
        $title = preg_replace("/([a-z])([A-Z])/", '\1 \2', $title);
        $sql->bindValue(':name', $title);
        $sql->bindValue(':url',  $posts[1][$key]);    
        $sql->execute();
        if($debug)
        	echo $title."\n";
        if($i > 2 && $sql->rowCount() == 0) break 2;
    }
} while(1);

echo 'carreck.com: ' . (array_pop($db->query('SELECT count(*) FROM posts;')->fetch()) - $postsCount) . "\n";
$postsCount = array_pop($db->query('SELECT count(*) FROM posts;')->fetch());

$base = "http://forums.lazygirls.info/";
$i = 60;
do {
    $url = $base;
    if($i > 60) $url = $base . "?aloc=" . $i;
    if($debug)
    	echo $url . "\n";
    $listPage = doCurl($url);
    preg_match_all('#<div class="f_[d|c]".+?<div class="f_i"#s', $listPage, $posts);
    if(count($posts[0]) == 0) {
        echo 'ERROR: No posts found for '.$url."\n";
        break;
    }
    foreach($posts[0] as $post){
        preg_match('#lz-no-underline">([^<]+?)</span>#', $post, $name);
        preg_match('#(http://forums.lazygirls.info/\d*.html)" >([^<]+?)<#', $post, $postParts);
        
        $sql = $db->prepare('INSERT INTO `posts` (`name`, `url`) VALUES (:name, :url);');
        $title = $name[1] . ' - ' . $postParts[2];
        $sql->bindValue(':name', $title);
        $sql->bindValue(':url',  $postParts[1]); 
        $sql->execute();
        if($debug)
        	echo $title."\n";
        if($i > 60 && $sql->rowCount() == 0) break 2;
    }
    $i += 60;
} while(1);

echo 'forums.lazygirls.info: ' . (array_pop($db->query('SELECT count(*) FROM posts;')->fetch()) - $postsCount) . "\n";

// Cleanup
$postsCount = array_pop($db->query('SELECT count(*) FROM posts WHERE `reject` = 1;')->fetch());
$rejects = array('party', 'arrives', 'leaves', 'arriving', 'leaving', 'shopping', 'out', 'gala', 'award', 'awards', 'airport', 'premiere', 'at % in', 'candids', 'fashion show');
foreach($rejects as $reject) {
    $sql = $db->prepare('UPDATE `posts` SET `reject` = 1 WHERE `name` NOT LIKE "%photoshoot%" AND `name` LIKE :name;');
    $sql->bindValue(':name', '% '.$reject.' %');
    $sql->execute();
}
echo 'rejected: ' . (array_pop($db->query('SELECT count(*) FROM posts WHERE `reject` = 1;')->fetch()) - $postsCount) . "\n";


function doCurl($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
    return curl_exec($ch);
}





