<?php

$url = $_GET['url'];
$json = !empty($_GET['json']);
$baseDir = "/Volumes/Dropbox/scratch/";

if(substr($url, 0, 10) == 'showthread')
    $url = 'http://celebutopia.net/forums/' . $url;

$url = html_entity_decode($url);
$headers = get_headers($url, 1);
$urlParts = parse_url($url);
$host = $urlParts['host'];
if($host == "www.celebutopia.org" || $host == "www.hotflick.net"){
    $postPage = doCurl($url);
} else {
    $postPage = file_get_contents($url);
}
//file_put_contents("postpage.html", $postPage);
//$postPage = file_get_contents("postpage.html");
$postPage = str_replace("\n", " ", $postPage);
$urlParts = parse_url($url);
$host = $urlParts['host'];
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
            continue 2;
        }
        $dir = parseTitle($title, "Celebutopia");
        preg_match('#postcontent.+?/blockquote#', $postPage, $post);
        if(count($post) == 0){
            fail("no post found for $url using $host");
            continue 2;
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
        do {
            preg_match("#xajax_displaycustomlist\((\d*),(\d*)#", $postPage, $nextPage);
            if(count($nextPage) < 3) break;
                        
            $ch = curl_init('http://www.listal.com/ajax/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_POST, 3);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "xjxfun=displaycustomlist&xjxargs[]=N{$nextPage[1]}&xjxargs[]=N{$nextPage[2]}");
            $postPage = curl_exec($ch);
            
            preg_match_all("#href='\/viewimage\/(.+?)'#", $postPage, $pageLinks);
            $links = array_merge($links, $pageLinks[1]);
        } while (1);
        foreach($links as $id => $value){
            $links[$id] = 'http://ilarge.listal.com/image/' . $value . '/10000full-' . $name . '.jpg';
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
    case "imgur.com":
        preg_match("#<title>(.+?) - Album on Imgur</title>#s", $postPage, $title);
        if(count($title) < 2 || count($title[1]) == 0) die("no title found for $url using $host\n");
        $title = $title[1];
        $dir = parseTitle("Imgur Gallery ".$title, "Imgur");
        preg_match_all('#class="post-image-placeholder" src="([^"]+?)"#', $postPage, $links);
        $links = array_pop($links);
        foreach($links as $linkid => $link) {
            $links[$linkid] = "http:$link";
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
    if($linkHost == 'ilarge.listal.com') {
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
            case "rosemciversource.net":
                preg_match('#<img.+?src="(.+?)".+?id="fullsize_image"#', $imagePage, $largeImg);
                $largeImg = "http://rosemciversource.net/gallery/".$largeImg[1];
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
    header("Content-Type: application/json");
    echo json_encode($largeImages);
    
} else {
    $nameInfo = $largeImages[0]['file'];
    $nameInfo = dirname($nameInfo);
    $nameInfo = explode('/', $nameInfo);
    $title = array_pop($nameInfo);
    $name = array_pop($nameInfo);
    header('Content-Description: File Transfer');
    header("Content-Type: text/plain");
    header('Content-Disposition: attachment; filename=' . str_replace(' ', '-', $name.'_'.$title.'.sh')); 
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
trash /Volumes/Macintosh\ HD/Users/dennis/Downloads/<?php echo str_replace(' ', '-', $name.'_'.$title.'.sh') ?>
<?php
}


function sanitize($string)
{
$regex = <<<'END'
/
  (
    (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
    ){1,100}                      # ...one or more times
  )
| ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
| ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
/x
END;
    $string = trim($string);
    $string = html_entity_decode($string);
    $string = preg_replace_callback($regex, "utf8replacer", $string);
    $transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'e', 'ё' => 'e', 'Ё' => 'e', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja');
    $string = str_replace(array_keys($transliterationTable), array_values($transliterationTable), $string);
    $string = str_replace("?", "", $string);
    $string = str_replace("-", " ", $string);
    $string = preg_replace('#([^\w ])#u', "", $string);
    return $string;
}
function get_http_response_code($url) {
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}
function fail($msg){    
    $er = fopen('error.log', 'a');
    fputs($er, date('c') . ': '.$msg."\n");
    fclose($er);
}
function linkError($link){
    global $json;
    if(!$json) {
        echo "Link error $link\n";
    }
    fail("Link error $link");
}
function file_url($url){
    $parts = parse_url($url);
    $path_parts = array_map('rawurldecode', explode('/', $parts['path']));

    return
        $parts['scheme'] . '://' .
        $parts['host'] .
        implode('/', array_map('rawurlencode', $path_parts))
    ;
}
function parseTitle($title, $siteName){
    global $baseDir, $thumbs;
    $title = sanitize($title);
    $titles = explode(" ", trim($title));
    $name = array_shift($titles);
    $name = ucwords(strtolower($name));
    if(!in_array($name, array("Beyonce", "Kesha", "Rihanna", "Pink", "Alizee"))){
        $t = trim(array_shift($titles));
            if($t != "" && $t != "-")
                $name .= " $t";
    }
    $name = ucwords(strtolower($name));
    $title = "";
    while(count($titles) && strtolower($titles[0]) != strtolower($siteName)){
        $t = trim(array_shift($titles));
        if(in_array($name, array("Jennifer Love", "Lana Del", "Jaime Ray", "Emanuela De", "Pia Mia", "Carrie Anne", "Jenna Louise", "Carly Rae", "Sara Jean"))){
            $name .= " $t";
            continue;
        }
        if($t != "" && $t != "-")
            $title .= " $t";
    }
    $name = ucwords(strtolower($name));
    $title = trim($title);
    if($name == "" || $title == "") die("name parse error\n");
    $dir = $baseDir . $name . "/" . $title . "/";
    return $dir;
}
function utf8replacer($captures) {
  if ($captures[1] != "") {
    // Valid byte sequence. Return unmodified.
    return $captures[1];
  }
  elseif ($captures[2] != "") {
    // Invalid byte of the form 10xxxxxx.
    // Encode as 11000010 10xxxxxx.
    return "\xC2".$captures[2];
  }
  else {
    // Invalid byte of the form 11xxxxxx.
    // Encode as 11000011 10xxxxxx.
    return "\xC3".chr(ord($captures[3])-64);
  }
}
function doCurl($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
    return curl_exec($ch);
}


?>
