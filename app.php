<?php
/**
 * @author		Spartacus
 * @address		www.spartacuswallpaper.com
 */
error_reporting(E_ERROR | E_WARNING | E_PARSE);
$base_dir = dirname(__FILE__).'/';
require_once($base_dir."db.php");
date_default_timezone_set("UTC");
$site_dir   = $base_dir . "_site/";
$theme_dir  = $base_dir . "theme/";
$assets_dir = $base_dir . "assets/";
$page_size  = 18;
$rebuild = false;
$baseurl = strpos($base_dir, 'development') ?  "http://dev.spartacuswallpaper.com/" : "http://spartacuswallpaper.com/";

function tag($tagName, $content, $html){
    $tagName = str_replace("{{", "", str_replace("}}", "", $tagName));
    return preg_replace("#\{\{$tagName\}\}#", $content, $html);
}

function tag_all($tagName, $object, $html){
    $tagName = str_replace("{{", "", str_replace("}}", "", $tagName));
    preg_match_all("#\{\{$tagName (\S*)\}\}#", $html, $tags);
    foreach($tags[0] as $i => $tag){
        if(isset($object[$tags[1][$i]]))
            $html = tag($tag, $object[$tags[1][$i]], $html);
    }
    return $html;
}

function tags_parse($html){
    preg_match_all("#{{(\S*)[ ]*(\S*)}}#", $html, $tags);
    foreach($tags[0] as $i => $tag){
        $tagContent = tag_parse($tags[1][$i], $tags[2][$i]);
        $html = tag($tag, $tagContent, $html);
    }
    return $html;
}

function tag_parse($tagName, $arg = null){
    global $db, $theme_dir, $assets_dir, $baseurl;
    switch($tagName){
        case "kinds":
            $result = $db->query("SELECT * FROM image_kind WHERE position IS NOT NULL AND exclude = 0 ORDER BY mobile, position ASC;");
            $kindsHtml = array();
            while($kind = $result->fetchArray()){
                $kindsHtml[(Int)$kind['mobile']][] = "<li><a href='{$baseurl}tag/{$kind['path']}'>{$kind['label']}</a></li>";
            }
            $html = "<li><span>Desktop Computer</span><ul>".implode($kindsHtml[0])."</ul></li>";
	        $html .= "<li><span>Phone</span><ul>".implode($kindsHtml[1])."</ul></li>";
	        return $html;
        case "tag_years":
            $result = $db->query("SELECT SUBSTR(published_at, 0, 5) as year FROM entry GROUP BY year ORDER BY published_at DESC;");
            $html = "";
            while($row = $result->fetchArray()){
                $html .= "<li><a href='{$baseurl}tag/{$row['year']}'>{$row['year']}</a></li>";
            }
            return $html;
        case "tag_10":
            $result = $db->query("SELECT t.title, t.slug, t.id, count(*) as count, e.id as entry_id FROM tag t JOIN entry_tag et ON et.tag_id = t.id JOIN entry e ON et.entry_id = e.id WHERE e.published IS NOT NULL AND name = 1 GROUP BY tag_id ORDER BY `count` DESC, t.title LIMIT 10;");
            $html = "";
            while($tag = $result->fetchArray()){
                $thumb = $db->prepare("SELECT e.* FROM image i JOIN entry e ON e.id = i.entry_id JOIN entry_tag et ON et.entry_id = e.id JOIN image_kind k ON k.id = i.kind WHERE et.tag_id = :tag_id AND e.published IS NOT NULL AND k.mobile = 1 GROUP BY i.path ORDER BY RANDOM() LIMIT 1;");
                $thumb->bindParam(':tag_id', $tag['id']);
                $thumb = $thumb->execute()->fetchArray();
                $thumb['title'] = $tag['title'];
                //$thumb['url_path'] = 'tag/' . $tag['slug'];
                $html .= tag_entry($thumb, null, 99, 'tag', 'col-md-12 col-sm-4 col-xs-6');
            }
            return $html;
        case "calendar":
            $calEntry = $db->query("SELECT e.*, k.path as kind, i.path as image FROM entry e JOIN image i ON i.entry_id = e.id JOIN image_kind k ON i.kind = k.id AND k.exclude != 1 WHERE `queue` = 2 AND date(`published_at`) <= date('now') ORDER BY k.position ASC, `published_at` DESC LIMIT 1;");
            $calEntry = $calEntry->fetchArray();
            $html = '<div class="col-md-12 col-sm-6 col-xs-6"><a href="'.$baseurl.'tag/calendar" title="Calendar Series"><span>'.$calEntry['title'].'</span><img src="'.get_cache_url($calEntry['kind'].'/'.$calEntry['filename'], 400).'" title="'.$calEntry['title'].'" /></a></div>';
            return $html;
        case "baseurl":
            return $baseurl;
        case "date_year":
            return date("Y");
        default:
            return "";
    }
}

function get_layout($long = false, $file = ''){
    global $theme_dir;
    $html = $file ? file_get_contents($theme_dir."layout/" . $file . ".phtml") : file_get_contents($theme_dir."layout/default.phtml");
    preg_match_all("#{{include (\S*)}}#", $html, $tags);
    foreach($tags[0] as $i => $tag){
        if($long && $tags[1][$i] == "ad-sidebar.phtml") {
            $html = tag($tag, file_get_contents($theme_dir."includes/long-ad-sidebar.phtml"), $html);
        } else {
            $html = tag($tag, file_get_contents($theme_dir."includes/".$tags[1][$i]), $html);
        }
    }
    return $html;
}

function tag_entry($entry, $layout = null, $count, $layoutType = 'tag', $classes = null, $mobile = true) {
    global $baseurl, $db, $theme_dir, $page_size;
    if($layout == null)
        $layout = file_get_contents($theme_dir."layout/tag.phtml");
    $entry['slug'] = $baseurl . $entry['url_path'];

    $preview = $count >= $page_size ? 'thumb' : 'preview';
    $thumbsize = 660;
    if($classes) {
        $entry['classes'] = $classes;
    } else {
        if($preview == 'thumb')
            $entry['classes'] = 'col-sm-4';
        else
            $entry['classes'] = 'col-sm-6';
            
        if($mobile)
            $entry['classes'] .= ' col-xs-12';
        else
            $entry['classes'] .= ' col-xs-6';
    }
    
    $hasMobile = false;    
    if($mobile) {
        $imageResult = $db->query("SELECT k.path as dir, i.path as file, k.position FROM image i JOIN image_kind k ON k.id = i.kind WHERE entry_id = {$entry['id']} AND k.mobile = 1 ORDER BY k.position ASC LIMIT 2;");
        $images = $db->fetchAll($imageResult);
        $mobileImages = '<div class="entry-images visible-xs">';
        $i = 0;
        foreach($images as $image) {
            $hasMobile = true;
            $mobileImage = $baseurl."gallery/".$image['dir']."/".$image['file'];
            if($i%2 == 0)
                $mobileImages .= '<div class="image-box col-xs-12 col-sm-4">';
                $mobileImages .= '<a href="'.$entry['slug'].'" class="image col-xs-6" title="'.$entry['title'].'"><img src="'.get_cache_url($mobileImage, 340).'" alt="'.$entry['title'].'"/></a>';
            if(count($images) == 1 || $i++%2 == 1)
                $mobileImages .= '</div>';
        }
    }
    
    if(!isset($entry['thumb']) || empty($entry['thumb'])) {
        $imageResult = $db->query("SELECT k.path || '/' || i.path as thumb FROM image i JOIN image_kind k ON k.id = i.kind WHERE entry_id = {$entry['id']} AND k.exclude != 1 ORDER BY k.position ASC LIMIT 1;");
        $image = $imageResult->fetchArray();        
        $entry['thumb'] = $image['thumb'];
    }    
    $entry['thumb'] = get_cache_url($entry['thumb'], $thumbsize);
    
    if($hasMobile) {
        $entry['mobile_images'] = $mobileImages . '</div>';
        $entry['mobile'] = 'hidden-xs';
    }
    if(!isset($entry['date']))
        $entry['date'] = format_date($entry['published_at'], true);

    return tag_all("tag", $entry, $layout);    
}

function getMore($count = 1, $tagIds = null, $class='col-xs-4', $excludeIds = null) {
    global $db, $baseurl, $theme_dir;
    $tagLayout = file_get_contents($theme_dir."layout/tag.phtml");
    
    $html = '';
    $head = 'SELECT e.* FROM entry e';
    if(is_array($excludeIds))
        $tail = ' AND e.id NOT IN('.implode(',', $excludeIds).') ORDER BY RANDOM() LIMIT ' . $count . ';';
    else
        $tail = ' ORDER BY RANDOM() LIMIT ' . $count . ';';
    
    $moreCount = 0;
    if(is_array($tagIds)) {
        if(count($tagIds) > 1) {
            $tail = str_replace('LIMIT '.$count, 'LIMIT 3', $tail);
            $count = 3;
        }
        $tags = $db->prepare('SELECT * FROM tag WHERE id IN('.implode(',', $tagIds).') ORDER BY name DESC, title ASC;');
        $tags = $tags->execute();
        
        while($tag = $tags->fetchArray()) {
            $sql = $head . ' JOIN entry_tag t ON t.entry_id = e.id WHERE published IS NOT NULL AND t.tag_id = :tag' . $tail;
            $entries = $db->prepare($sql);
            $entries->bindValue(':tag', $tag['id']);

            $entries = $entries->execute();
            $entries = $db->fetchAll($entries);
            
            if(count($entries) == 0) continue;
            $html .= '<h3 class="col-xs-12">More <a href="'.$baseurl.'tag/'.$tag['slug'].'" title="'.$tag['title'].'">'.$tag['title'].'</a> Wallpaper</h3><div class="row">';
            foreach($entries as $entry) {
                $excludeIds[] = $entry['id'];
                $tail = ' AND e.id NOT IN('.implode(',', $excludeIds).') ORDER BY RANDOM() LIMIT ' . $count . ';';
                $html .= tag_entry($entry, $tagLayout, 99, 'tag', $class);
                $moreCount++;
            }
            $html .= '</div>';
        }
    }
    if($moreCount == 0) {
        $sql = $db->query($head . ' WHERE published IS NOT NULL' . $tail);
        $entries = $db->fetchAll($sql);
        $html .= '<div class="row">';
        $html .= '<h3 class="col-xs-12">More Wallpaper</h3>';
        foreach($entries as $entry) {
            $html .= tag_entry($entry, $tagLayout, 99, 'tag', $class);
            $moreCount++;
        }
        $html .= '</div>';
    }

    return $html;
}

function pager($url, $current, $count) {
    global $baseurl;
    $html = '<script type="text/javascript">
    var curPage = '.$current.';
    </script>';
    if($count > 1) {
        if($current < $count) 
            $html .= '<button class="btn btn-more btn-lg"><a id="show_more">Show More</a></button>';
    
        $html .= '<ul class="pager /* hidden */">';
        
        $start = max(array($current-3, 1));
        $end = min(array($count, $current+3));
                        
        if($current != $count) {
            $html .= '<li><a href="'.$baseurl.$url.'/page/'.($current+1).'" title="older">&lsaquo; older</a></li>';
        } else {
            $html .= '<li><span>&laquo;</span></li>';
        }
        
        if($end < $count)
            $html .= '<li><a href="'.$baseurl.$url.'/page/'.($count).'" title="page '. $count .'">'. $count .'</a></li>...';
         
        for($i = $end; $i >= $start; $i--){
            if($i == $current)
                $html .= '<li class="current"><span>'.$i.'</span></li>';
            else
                $html .= '<li><a href="'.$baseurl.$url.'/page/'.$i.'" title="page '. $i .'">'.$i.'</a></li>';
        }
        
        if($start > 1)
            $html .= '...<li><a href="'.$baseurl.$url.'/page/1" title="page 1">1</a></li>';
        
        if($current != 1) {
            $html .= '<li><a href="'.$baseurl.$url.'/page/'.($current-1).'" title="newer">&rsaquo; newer</a></li>';
        } else {
            $html .= '<li><span>&rsaquo; newer</span></li>';
        }
        
        $html .= '</ul>';
    }
    return $html;
}

function write_file($slug, $content){    
    global $site_dir;
    $slug = str_replace(".html", "", $slug);
    $dir = $site_dir . $slug;
    if(!is_dir(rtrim($dir, "/"))) {
        mkdir(rtrim($dir, "/"), 0775, true);
    }
    
    file_put_contents($site_dir."$slug.html", $content);
    file_put_contents($site_dir."$slug/index.html", $content);
}

function del_tree($dir) { 
    $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
        (is_dir("$dir/$file")) ? del_tree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
} 

function recurse_copy($src,$dst) { 
    $dir = opendir($src); 
    @mkdir($dst, 0775); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                recurse_copy($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir); 
} 

function codeToName($code) {
	if(preg_match("#-[ivxlm]*\$#", $code)){
		$parts = explode("-", $code);
		$roman = strtoupper(array_pop($parts));
		array_push($parts, $roman);
		$code = implode("-", $parts);
	}
	return trim(ucwords(preg_replace("#(-|_)#", " ", $code)));
}

function format_date($date, $short = false) {
	$format = $short ? "M j, Y" : "l, F jS, Y";
	return date($format, strtotime($date));
}

function get_cache_url($image, $size) {
    global $baseurl;
    
    $filename = str_replace('.jpg', '', basename($image));
    $kind = basename(dirname($image));
       
    $encoded = transcode($kind . '/' . $filename);

    $url = $baseurl . 'gallery/cache/' . $size . '/' . $encoded . '.jpg';

    return $url;
}

function transcode($string){
    $key = array('0' => 'a', '-' => 'y', '_' => '4', 'a' => '0', 'b' => 'c', 'c' => 'b', 'd' => '3', 'e' => '6', 'f' => 'n', 'g' => 'o', 'h' => 'm', 'i' => 'j', 'j' => 'i', 'k' => 'q', 'l' => '9', 'm' => 'h', 'n' => 'f', 'o' => 'g', 'p' => 't', 'q' => 'k', 'r' => 'w', 's' => '8', 't' => 'p', 'u' => '1', 'v' => '2', 'w' => 'r', 'x' => 'z', 'y' => '-', 'z' => 'x', '1' => 'u', '2' => 'v', '3' => 'd', '4' => '_', '5' => '7', '6' => 'e', '7' => '5', '8' => 's', '9' => 'l', '/' => '/');
    
    $in = str_split($string);
    $out = array();
    foreach($in as $v) {
        $out[] = $key[$v];
    }
    return implode('', $out);
}

function slog($message, $error = false) {
    global $base_dir;
    $logdir = $base_dir . 'var/log/';
    if($error)
        $file = $logdir . 'error.log';
    else
        $file = $logdir . 'system.log';
     
    if(!is_dir(dirname($file))){
        mkdir(dirname($file), 0777, true);
    }        
        
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    $message = date('[Y-m-d H:i:s] ') . $message . "\n";
    
    echo $message;
    
    $fp = fopen($file, 'a');
    fputs($fp, $message);
    fclose($fp);
}

function elog($message, $entryId, $date = null) {
    global $db;
            
    $sql = $db->prepare("INSERT INTO entry_log (entry_id, message, created_at) VALUES (:entry_id, :message, :created_at);");
    if($date == '') $date = date('Y-m-d H:i:s');
    $sql->bindParam(':entry_id', $entryId);
    $sql->bindParam(':message', $message);
    $sql->bindParam(':created_at', $date);
    $sql->execute();
}

