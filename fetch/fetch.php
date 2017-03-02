<?php

require_once 'functions.php';

if(!empty($_GET)) {
	$url  = $_GET['url'];
	$json = ! empty( $_GET['json'] );
} else {
    $cli = true;
    $url = $argv[1];
    $json = ! empty($argv[2]);
}
$baseDir = "/Volumes/Dropbox/scratch/";

if(substr($url, 0, 10) == 'showthread')
    $url = 'http://celebutopia.net/forums/' . $url;

$url = html_entity_decode($url);
slog('Fetching '.$url);
$headers = get_headers($url, 1);
$urlParts = parse_url($url);
$host = $urlParts['host'];
if($host == "www.theplace2.ru" || $host == "www.hotflick.net"){
    $postPage = doCurl($url);
} else {
    $postPage = file_get_contents($url);
}
file_put_contents("postpage.html", $postPage);
//$postPage = file_get_contents("postpage.html");
$postPage = str_replace("\n", " ", $postPage);
$urlParts = parse_url($url);
$host = $urlParts['host'];
slog('Host '.$host);
switch($host){
    // ! celebutopia blog post
    case "www.celebutopia.org":
    case "celebutopia.org":
        preg_match("#<title>(.+?)</title>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle($title, "Celebutopia");
        preg_match_all('#gallery-icon.+?href="(.+?)"#', $postPage, $links);
        if(count($links) != 2){
            fail("no images found for $url using $host");
            continue;
        }
        $links = $links[1];
        break;
    // ! celebutopia forum post
    case "celebutopia.net":
    case "www.celebutopia.net":
        preg_match("#<title>(.+?)</title>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        if(preg_match("#fact of the day#i", $title)){
            fail("No images here.");
            continue;
        }
        $dir = parseTitle($title, "Celebutopia");
        preg_match('#postcontent.+?/blockquote#', $postPage, $post);
        if(count($post) == 0){
            fail("no post found for $url using $host");
            continue;
        }
        $post = array_pop($post);
        preg_match_all('#href="(http.+?)"#', $post, $links);
        if(count($links) != 2){
            fail("no images found for $url using $host");
            continue;
        }
        $links = $links[1];
        foreach($links as $key => $link){
            if(strpos($link, "celebutopia.net")) unset($links[$key]);
        }
        break;
    // ! hawtcelebs post
    case "www.hawtcelebs.com":
        preg_match("#<title>(.+?)</title>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle($title, "HawtCelebs");
        preg_match('#class="postcontent".+?<!--Ad Injection:bottom#', $postPage, $post);
        if(count($post) == 0) die("no post found for $url using $host\n");
        $post = array_pop($post);
        preg_match_all("#href=[\"|'](http.+?)[\"|']#", $post, $links);
        if(count($links) != 2){
            fail("no images found for $url using $host");
            continue;
        }
        $links = $links[1];
        break;
    // ! lazygirls post / forums
    case "www.lazygirls.info":
    case "forums.lazygirls.info":
        if($host == 'forums.lazygirls.info'){
            preg_match("#<title>(.+?)</title>#", $postPage, $title);
            if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
            $title = $title[1];
            $dir = parseTitle($title, "Lazygirls");
            preg_match_all('#<a href="([^"]+?)"><img#s', $postPage, $links);
        } else {
            preg_match("#<title>(.+?) - .+?</title>#", $postPage, $title);
            $name = $title[1];
            preg_match('#<div class="name".+?>(.+?)</div>#', $postPage, $title);
            if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
            $title = $name . " " . $title[1];
            $dir = parseTitle($title, "Lazygirls");
            preg_match('#title="post".+?</table>#', $postPage, $post);
            if(count($post) == 0) die("no post found for $url using $host\n");
            $post = array_pop($post);
            preg_match_all('#href="(http.+?)"#', $post, $links);
            if(count($links) != 2){
                fail("no images found for $url using $host");
                continue;
            }
        }
        $links = array_pop($links);
        
        foreach($links as $key => $link){
            $link .= "?display=fullsize";
            if(strpos($link, "profiles")) {
                unset($links[$key]);
                continue;
            }
            $link = html_entity_decode($link);
            $headers = get_headers($link, 1);
            if(preg_match("#301 Moved Permanently#", $headers[0])){
                $link = trim($headers['Location']) . "?display=fullsize";
            }
            $imagePage = file_get_contents($link);
            preg_match('#href="(.+?)".+?>View original image</a>#', $imagePage, $largeImg);
            if(count($largeImg) > 0){
                $links[$key] = array_pop($largeImg);
            } else {
                $links[$key] .= "?display=fullsize";
            }
        }
        break;
    // !NCF
    case "www.nudecelebforum.com":
        preg_match("#<title>(.+?)</title>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle($title, "Nude Celeb Forum");
        preg_match_all('#<!-- message -->(.+?)<!-- / message -->#s', $postPage, $posts);
        $posts = array_pop($posts);
        
        $links = array();
        foreach($posts as $post) {
            preg_match_all('#a href="([^"]+?)" target="_blank"><img src#s', $post, $postlinks);
            $links = array_merge($links, $postlinks[1]);
        }
        break;        
    // ! gotceleb post
    case "www.gotceleb.com":
        preg_match("#<title>(.+?)</title>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle($title, "Gotceleb");
        preg_match_all("#gallery-icon.+?href='(.+?)'#", $postPage, $links);
        if(count($links) != 2){
            fail("no images found for $url using $host");
            continue;
        }
        $links = $links[1];
        foreach($links as $key => $link){
            $links[$key] = $link . "/full-image";
        }
        break;
    // ! fabzz post
    case "www.fabzz.com":
    case "fabzz.com":
    case "fabmansion.com":
        preg_match("#<title>(.+?)</title>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle($title, "Fabzz");
        preg_match_all("#gallery-icon.+?href='(.+?)'#", $postPage, $links);
        if(count($links) != 2){
            fail("no images found for $url using $host");
            continue;
        }
        $links = $links[1];
        foreach($links as $key => $link){
            $links[$key] = $link . "/full-image/";
        }
        break;
    // ! carreck post
    case "www.carreck.com":
        preg_match("#posttitle.+?<a.+?>(.+?)</a>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle($title, "Carrek");
        preg_match('#<div class="postentry".+?</div>#', $postPage, $post);
        if(count($post) == 0) die("no post found for $url using $host\n");
        $post = array_pop($post);
        preg_match_all('#href="(http.+?)"#', $post, $links);
        if(count($links) != 2){
            fail("no images found for $url using $host");
            continue;
        }
        $links = $links[1];
        break;
    // ! superiorpics post
    case "forums.superiorpics.com":
        preg_match("#<title>(.+?)</title>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle($title, "SuperiorPics");
        $links = array();
        preg_match('#<div id="body0".+?</td>#', $postPage, $post);
        $post = array_pop($post);
        preg_match_all('#a href="([^"]+?)"[^>]+?target="_blank"#', $post, $links);
        $links = array_pop($links);
        break;
    // ! listal
    case "www.listal.com":
        preg_match("#<title>(.+?)</title>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $dir = parseTitle($title[1], "Listal");
        $dirParts = array_reverse(explode('/', $dir));
        $name = strtolower(str_replace(' ', '-', $dirParts[2]));
        preg_match_all("#href='.+?\/viewimage\/(.+?)'#", $postPage, $pageLinks);
        $links = $pageLinks[1];
        preg_match("#&\\#187; (\\d*)#", $postPage, $lastPage);
        if(empty($lastPage[1]))
	        preg_match("#>(\\d*)</a>\\s<[^>]+?>Next#", $postPage, $lastPage);

        $lastPage = empty($lastPage[1]) ? 100 : $lastPage[1];
        slog('last Page is '.$lastPage);
        $page = 2;
        do {
            unset($pageLinks);
            $postPage = doCurl($url . '//' . $page++);
            slog('Listal page '.$page);
            preg_match_all("#href='.+?\/viewimage\/(.+?)'#", $postPage, $pageLinks);
            $links = array_unique(array_merge($links, $pageLinks[1]));
        } while ($page <= $lastPage);
        
        foreach($links as $id => $value){
            $links[$id] = 'http://iv1.lisimg.com/image/' . $value . '/10000full-' . $name . '.jpg';
        }
        break;
    // ! rosemciversource.net :)
    case "rosemciversource.net":
        preg_match("#<title>(.+?) - RoseMcIverSource Gallery</title>#", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle("Rose McIver - ".$title, "RoseMcIverSource");
        preg_match_all('#a href="displayimage.php[^"]+?pid=(\d+?)\##', $postPage, $links);
        $links = array_pop($links);
        foreach($links as $linkid => $pid) {
            $links[$linkid] = "http://rosemciversource.net/gallery/displayimage.php?pid={$pid}&fullsize=1";
        }
        break;
    // ! ravissante.org
    case 'www.ravissante.org':
        preg_match("#<title>(.+?)</title>#s", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle($title." Ravissante", "");
        preg_match_all('#href="(http://imgbox.+?)"#', $postPage, $links);
        if(count($links) != 2){
            fail("no images found for $url using $host");
            continue;
        }
        $links = $links[1];
        break;
    // ! imgur.com
    case "imgur.com":
        preg_match("#<title>(.+?) - Album on Imgur</title>#s", $postPage, $title);

        if(count($title) > 0 && count($title[1]) == 0)
            $dir = parseTitle("Imgur Gallery ".$title[1], "Imgur");
        else
            $dir = $baseDir . "Imgur Gallery/" . sanitize($urlParts['path']) . "/";
            
        preg_match_all('#property="og:image" content="([^"]*)"#', $postPage, $links);
        $links = array_pop($links);
        foreach($links as $linkid => $link) {
            $link = str_replace('.jpg', '', $link);
            $link = str_replace('.png', '', $link);
            $links[basename($link)] = "http:$link";
        }
        break;
    case "www.theplace2.ru":
        preg_match("#m_title.><span>([^<]+?)</span>#s", $postPage, $match);
        $name = trim($match[1]);
        if($name == '') {
            preg_match("#forum[^>]+?alt=\"([^\"]+?)\" class=\"pic\"#s", $postPage, $match);
            $name = trim($match[1]);
        }
        if($name == '') {
            preg_match("#<h1>([^<]+?)</h1>#s", $postPage, $match);
            $name = trim($match[1]);
        }
        file_put_contents('postpage.html', $postPage);

        preg_match("#Subject:.+?10\">([^<]+?)</div>#s", $postPage, $match);
        $title = trim($match[1]);
        preg_match("#Message:.+?10\">([^<]+?)</div>#s", $postPage, $match);
        $title .= ' '. trim($match[1]);
fail($title);
        $nameParts = explode(' ', $name);
        foreach($nameParts as $part) {
            $title = trim(preg_replace('#'.strtolower($part).'#i', '', $title));
        }
        if(strlen($title) < 10) {
            $title = "theplace2 " . preg_replace("#\\D#", '', str_replace($host, '', $url));
        }

        $sql = $db->prepare('UPDATE `posts` SET `name` = :name WHERE `url` = :url;');
        $sql->bindValue(':url',  $url);
        $sql->bindValue(':name', "$name - $title");
        $sql->execute();
        $dir = $baseDir . sanitize($name) . '/' . sanitize($title);
fail("$name - $title");
        preg_match_all('#"(/forum/pics/.+?)"#', $postPage, $links);
        $links = array_pop($links);
        foreach($links as $linkid => $link) {
            $link = preg_replace("#_s(\\....)$#", "\\1", $link);
            $link = str_replace(' ', '%20', $link);
            $link = "http://{$host}{$link}";
            $links[$linkid] = $link;
        }
        break;
    default:
        die("no processing found for $host\n");
}

// ! -- image pages
$largeImages = array();

foreach($links as $linkId => $link){
    $linkHost = parse_url($link);
    $linkHost = $linkHost['host'];
    if($linkHost == 'iv1.lisimg.com') {
        $linkParts = array_reverse(explode('/', $link));
        $id = $linkParts[1];
        $destfile = $dir . $id . '.jpg';
        $largeImages[] = array(
            'url' => $link,
            'file' => $destfile
        );
        continue;
    }
    if(filter_var($link, FILTER_VALIDATE_URL) === FALSE){
        linkError($link);
        continue;
    }
    if($linkHost == 'www.theplace2.ru') {
        $largeImg = $link;
        $destfile = $dir . '/' . basename($link);
        $largeImages[] = array(
            'url' => $link,
            'file' => $destfile
        );
        continue;
    }
    $headers = get_headers($link, 1);
    if(preg_match("#30. Moved#", $headers[0])){
        $link = $headers['Location'];
        $headers = get_headers($link, 1);
    }
    if(is_array($headers['Content-Type'])){
        $headers['Content-Type'] = array_pop($headers['Content-Type']);
    }
    if(preg_match("#404 Not Found#", $headers[0]) || $link == "http://imeezo.com/404"){
        linkError($link);
        continue;
    }
    if(preg_match("#text/html#", $headers['Content-Type'])){
        $linkHost = parse_url($link);
        $linkHost = $linkHost['host'];
        $linkMatch = explode(".", $linkHost);
        while(count($linkMatch) > 2){
            array_shift($linkMatch);
        }
        $linkMatch = implode(".", $linkMatch);
        $link = html_entity_decode($link);
        $imagePage = file_get_contents($link);
        //file_put_contents("imagepage.html", $imagePage);
        switch($linkMatch){
            // ! image processing
            case "celebutopia.org":
                $ch = curl_init($link);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_AUTOREFERER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
                $imagePage = curl_exec($ch);
                preg_match('#class="attachment".+?href="(.+?)"#', $imagePage, $largeImg);
                break;
            case "hawtcelebs.com":
                $largeImg = $link;
                break;
            case "lazygirls.info":
                preg_match('#<img.+?onclick="fullsize.+?src="(.+?)"#', $imagePage, $largeImg);
                break;
            case "gotceleb.com":
            case "fabzz.com":
            case "fabmansion.com":
                preg_match('#<a title="open the full-size image in a new window" href="(.+?)"#', $imagePage, $largeImg);
                break;
            case "someimage.com":
                preg_match("#<img.+?src='(.+?)'.+?id='viewimage'#", $imagePage, $largeImg);

                break;
            case "imgbox.com":
                preg_match('#<img.+?class="image-content".+?src="(.+?)"#', $imagePage, $largeImg);
                break;
            case "vybzmagazine.com":
                preg_match('#<img.+?id="resized".+?src="(.+?)"#', $imagePage, $largeImg);
                break;
            case "imagevenue.com":
                preg_match('#<img id="thepic".+?SRC="(.+?)"#', $imagePage, $largeImg);
                if(count($largeImg) == 0){
                    linkError($link);
                    continue;
                }
                $largeImg = "http://".$linkHost."/".$largeImg[1];
                break;
            case "imageboss.net":
                preg_match('#<img id="thepic".+?src="(.+?)"#', $imagePage, $largeImg);
                break;
            case "storeimgs.net":
                preg_match("#http://storeimgs.net/upload/big/.+?.jpg#", $imagePage, $largeImg);
                break;
            case "imagebam.com":
                preg_match("#(http://.+?/download[^\"]+?)\"#", $imagePage, $largeImg);
                break;
            case "oncelebrity.com":
                preg_match('#<img.+?src="(.+?)".+?id="full_pic"#', $imagePage, $largeImg);
                break;
	        case "hotflick.net":
		        preg_match('#<img.+?id="img".+?src="(.+?)"#', $imagePage, $largeImg);
		        break;
            case "rosemciversource.net":
                preg_match('#<img.+?src="(.+?)".+?id="fullsize_image"#', $imagePage, $largeImg);
                $largeImg = "http://rosemciversource.net/gallery/".$largeImg[1];
                break;
            case "imageupper.com":
                preg_match('#<img id="img".+?src="(.+?)"#i', $imagePage, $largeImg);
                break;
            case "upix.me":
                $largeImg = str_replace("#", "", $link);
                break;
            case "youtube.com":
            case "vimeo.com":
            case "sendspace.com":
                fail("video or other");
                continue;
            default:
                fail("no image processing found for $link ($linkMatch)");
                if($json) {
                    $largeImages[] = array(
                        'msg' => "no image processing found for $link ($linkMatch)",
                        'error' => 1
                    );
                }
                continue;
        }
    } else {
        $largeImg = $link;
    }
    if(isset($largeImg)){
        if(is_array($largeImg)){
            if(count($largeImg) == 0){
                linkError($link);
                continue;
            }
            $largeImg = array_pop($largeImg);
        }
        $destFile = pathinfo($largeImg);
        $destFile = $destFile['basename'];
        $dest = $dir . $destFile;
        $largeImages[] = array(
            'url' => $largeImg,
            'file' => $dest
        );
    }
}

// ! End Main Block
// ! output
if($json) {
	header( "Content-Type: application/json" );
	echo json_encode( $largeImages );
} elseif ($cli) {
    $getDir = __DIR__ . '/get';
    if(!is_dir($getDir)) {
        mkdir($getDir, 0775);
	    chmod($getDir, 0775);
    }
    $k = 1;
    foreach($largeImages as $image) {
        $file = basename($image['file']);
        $parts = explode('/', $image['file']);
        array_pop($parts);
        $shoot = array_pop($parts);
        $name = array_pop($parts);
        $dest = $getDir . '/' . $name . '/' . $shoot . '/';
        if(!is_dir($dest)) {
            mkdir($dest, 0775, true);
	        chmod($dest, 0775);
	        chmod($getDir . '/' . $name, 0775);
        }
        file_put_contents($dest . $file, doCurl($image['url']));
        echo "$dest $file \n";
        slog($file . ' (' . $k++ . ' of ' . count($largeImages) . ')');
    }
} else {
    $nameInfo = $largeImages[0]['file'];
    $nameInfo = dirname($nameInfo);
    $nameInfo = explode('/', $nameInfo);
    $title = array_pop($nameInfo);
    $name = array_pop($nameInfo);
    header('Content-Description: File Transfer');
    header("Content-Type: text/plain");
    header('Content-Disposition: attachment; filename=' . str_replace(' ', '-', $name.'_'.$title.'.fetch')); 
    header('Content-Transfer-Encoding: binary');
    header('Connection: Keep-Alive');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
?>#!/bin/bash
echo "Name: <?php echo $name ?>"
echo "Title: <?php echo $title ?>"
<?php foreach($largeImages as $largeImage): ?>
curl --create-dirs "<?php echo $largeImage['url'] ?>" -o "<?php echo str_replace(':', '', $largeImage['file']) ?>"
<?php endforeach; ?>
trash /Volumes/Macintosh\ HD/Users/dennis/Downloads/<?php echo str_replace(' ', '-', $name.'_'.$title.'.fetch') ?>
<?php
}

