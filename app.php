<?php
/**
 * @author		Dennis Rogers
 * @address		www.drogers.net
 */
error_reporting(E_ERROR | E_WARNING | E_PARSE);
$base_dir = dirname(__FILE__).'/';
require_once($base_dir."db.php");
date_default_timezone_set("UTC");
$site_dir   = $base_dir . "_site/";
$theme_dir  = $base_dir . "theme/";
$assets_dir = $base_dir . "assets/";
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
    preg_match_all("#{{(\S*)\s*(\S*)}}#", $html, $tags);
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
            $result = $db->query("SELECT * FROM image_kind WHERE position IS NOT NULL ORDER BY position ASC;");
            $html = "";
            while($kind = $result->fetchArray()){
                $html .= "<li><a href='{$baseurl}tag/{$kind['path']}'>{$kind['label']}</a></li>";
            }
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
                $html .= tag_entry($thumb, null, 99, 'tag', 'col-md-12 col-sm-4 col-xs-12'); //'<div class="col-md-12 col-sm-4 col-xs-6"><a href="'.$baseurl.'tag/'.$tag['slug'].'" title="'.$tag['count'].' entries"><span>'.$tag['title'].'</span><img src="'.$baseurl."gallery/thumb/".$thumb['path'].'" title="'.$tag['title'].'" /></a></div>';
            }
            return $html;
        case "calendar":
            $calEntry = $db->query("SELECT * FROM `entry` WHERE `queue` = 2 AND PUBLISHED = 1 ORDER BY `published_at` DESC LIMIT 1;");
            $calEntry = $calEntry->fetchArray();
            $html = '<div class="col-md-12 col-sm-6 col-xs-12"><a href="'.$baseurl.'tag/calendar" title="Calendar Series"><span>'.$calEntry['title'].'</span><img src="'.$baseurl."gallery/thumb/".$calEntry['filename'].'" title="'.$calEntry['title'].'" /></a></div>';
            return $html;
        case "baseurl":
            return $baseurl;
        case "date_year":
            return date("Y");
        default:
            return "";
    }
}

function get_layout($long = false){
    global $theme_dir;
    $html = file_get_contents($theme_dir."layout/default.phtml");
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

function tag_entry($entry, $layout = null, $count, $layoutType = 'tag', $classes = null) {
    global $baseurl, $db, $theme_dir;
    if($layout == null)
        $layout = file_get_contents($theme_dir."layout/tag.phtml");
    $entry['slug'] = $baseurl . $entry['url_path'];
    $preview = $count > 12 ? 'thumb' : 'preview';
    if($classes) {
        $entry['classes'] = $classes;
    } else {
        if($preview == 'thumb')
            $entry['classes'] = 'col-xs-12 col-sm-4';
        else 
            $entry['classes'] = 'col-xs-12 col-sm-6';
    }
        
    $imageResult = $db->query("SELECT k.path as dir, i.path as file, k.position FROM image i JOIN image_kind k ON k.id = i.kind WHERE entry_id = {$entry['id']} AND (k.mobile = 1 OR k.path = '".$preview."') ORDER BY k.position ASC LIMIT 3;");
    $images = $db->fetchAll($imageResult);
    $mobileImages = '<div class="entry-images visible-xs">';
    $hasMobile = false;
    foreach($images as $image) {
        if($image['dir'] == $preview){
            $entry['thumb'] = $baseurl."gallery/".$image['dir']."/".$image['file'];
        } else {
            $hasMobile = true;
            $mobileImage = $baseurl."gallery/".$image['dir']."/".$image['file'];
            $mobileImages .= '<a href="'.$entry['slug'].'" class="image col-xs-6" title="'.$entry['title'].'"><img src="'.$mobileImage.'" alt="'.$entry['title'].'"/></a>';
        }
    }
    if($hasMobile) {
        $entry['mobile_images'] = $mobileImages . '</div>';
    } else {
        $entry['classes'] .= ' hidden-xs';
    }
    $entry['date'] = format_date($entry['published_at'], true);
    return tag_all("tag", $entry, $layout);    
}

function getMore($count = 1, $tags = null, $class='col-xs-4', $excludeIds = null) {
    global $db, $baseurl, $theme_dir;
    $tagLayout = file_get_contents($theme_dir."layout/tag.phtml");
    $html = '<h3 class="col-xs-12">More Wallpaper</h3>';

    $sql = 'SELECT e.* FROM entry e';
    if(is_array($tags)) {
        $sql .= ' JOIN entry_tag t ON t.entry_id = e.id WHERE t.tag_id IN(:tags)';
        $html = '<h3 class="col-xs-12">Similar Wallpaper</h3>';
    } else {
        $sql .= ' WHERE 1';
    } 
    if(is_array($excludeIds)) {
        $sql .= ' AND e.id NOT IN(:exclude)';
    }
    $sql .= ' ORDER BY RANDOM() LIMIT ' . $count . ';';
    
    $entries = $db->prepare($sql);
    if(is_array($tags))
        $entries->bindParam(':tags', implode(',', $tags));
    if(is_array($excludeIds))
        $entries->bindParam(':exclude', implode(',', $excludeIds));

    $entries = $entries->execute();
    $entries = $db->fetchAll($entries);
    
    if(count($entries) == 0) return '';
    foreach($entries as $entry) {
        $html .= tag_entry($entry, $tagLayout, 99, 'tag', $class);
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

