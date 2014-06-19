<?php
/**
 * @author		Dennis Rogers
 * @address		www.drogers.net
 */
$db = new SQLite3("spartacus");
date_default_timezone_set("UTC");
$site_dir = "_site/";
$theme_dir = "theme/";
$assets_dir = "assets/";
$rebuild = false;


function tag($tagName, $content, $html){
    $tagName = str_replace("{{", "", str_replace("}}", "", $tagName));
    return preg_replace("#\{\{$tagName\}\}#", $content, $html);
}

function tag_all($tagName, $object, $html){
    $tagName = str_replace("{{", "", str_replace("}}", "", $tagName));
    preg_match_all("#\{\{$tagName (\S*)\}\}#", $html, $tags);
    foreach($tags[0] as $i => $tag){
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
    global $db, $theme_dir, $assets_dir;
    switch($tagName){
        case "kinds":
            $result = $db->query("SELECT * FROM image_kind WHERE position IS NOT NULL ORDER BY position ASC;");
            $html = "";
            while($kind = $result->fetchArray()){
                $html .= "<li><a href='/tag/{$kind['path']}'>{$kind['label']}</a></li>";
            }
            return $html;
        case "tag_years":
            $result = $db->query("SELECT SUBSTR(published_at, 0, 5) as year FROM entry GROUP BY year ORDER BY published_at DESC;");
            $html = "";
            while($row = $result->fetchArray()){
                $html .= "<li><a href='/tag/{$row['year']}'>{$row['year']}</a></li>";
            }
            return $html;
        case "tag_20":
            $result = $db->query("SELECT title, slug, count(*) as count FROM tag t  JOIN entry_tag e ON e.tag_id = t.id WHERE list = 1 GROUP BY tag_id ORDER BY count DESC LIMIT 20;");
            $html = "";
            while($tag = $result->fetchArray()){
                $html .= "<li><a href='/tag/{$tag['slug']}' title='{$tag['count']} entries'>{$tag['title']}</a></li>";
            }
            return $html;
        case "date_year":
            return date("Y");
        default:
            return "";
    }
}

function get_layout(){
    global $theme_dir;
    $html = file_get_contents($theme_dir."layout/default.phtml");
    preg_match_all("#{{include (\S*)}}#", $html, $tags);
    foreach($tags[0] as $i => $tag){
        $html = tag($tag, file_get_contents($theme_dir."includes/".$tags[1][$i]), $html);
    }
    return $html;
}

function write_file($slug, $content){    
    global $site_dir;
    $slug = str_replace(".html", "", $slug);
    $dirs = explode("/", $slug);
    $dir = $site_dir;
    while(count($dirs)){
        $dir .= array_shift($dirs)."/";
        if(!is_dir(trim($dir, "/")))
            mkdir(trim($dir, "/"));
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
    @mkdir($dst); 
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
	$format = $short ? "M jS, 'y" : "l, M jS, Y";
	return date($format, strtotime($date));
}

