<?php
/**
 * @author		Dennis Rogers
 * @address		www.drogers.net
 */
require_once("app.php");

slog('------------------');
slog('Start Regeneration');
if(isset($argv[1])){
    slog('Full Mode');
    $rebuild = true;
}

// flush site and copy assets
if($rebuild && is_dir($site_dir)){
    exec("rm {$site_dir}gallery");
    del_tree($site_dir);
}

if(!is_dir($site_dir))
    mkdir($site_dir, 0777);
    
if(!is_dir($site_dir."gallery"))
    exec("ln -s /var/www/spartacuswallpaper.com/gallery/ {$site_dir}gallery");
recurse_copy($assets_dir, $site_dir);

// ! generate banner css
$banners = glob($assets_dir."images/banners/left/*.png");
$banners = array_merge($banners, glob($assets_dir."images/banners/right/*.png"));
$html = "";
foreach($banners as $i => $banner){
    $parts = array_reverse(explode("/", $banner));
    $file = $parts[0];
    $align = $parts[1];
    $imageUrl = $baseurl . 'images/banners/' . $align . '/' . $file;
    $topUrl = $baseurl . 'images/banners/top/' . $file;
    $html .= ".banner_$i, .banner_$i .banner-background { background-image: url({$imageUrl}); }\n";
    $html .= ".banner_$i .banner-feature { background-image: url({$topUrl}); }\n";
    if($align == "right"){
        $html .= ".banner_$i .logo { right:10px; left:auto; }\n";
    }
}
file_put_contents($site_dir."css/banner.css", $html);

$bannerJs = '
	jQuery(document).ready(function(){
  	var date = new Date();
  	var banner = Math.floor((Math.random() * '.count($banners).'));
  	jQuery(".header-banner.banner-border").addClass("banner_"+banner);
	});
';
file_put_contents($site_dir."js/banner.js", $bannerJs);

$header = file_get_contents($theme_dir . "includes/header.phtml");
$testHtml = '
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function(){
    	var counter = 1;
        jQuery(".header-banner.banner-border").each(function(){
            jQuery(this).attr("class", "").addClass("banner_" + counter++).addClass("header-banner").addClass("banner-border");
        });
	});
//]]>
</script>
<style type="text/css">
#sidebar { display:none; }
.content { width:100% !important; padding:0; }
.header-banner.banner-border {margin-bottom:10px; }
</style>
';
$testHtml .= str_repeat($header, (count($banners)-2));
$testPage = get_layout();
$testPage = tag('content', $testHtml, $testPage);
$testPage = tags_parse($testPage);
file_put_contents($site_dir."banner-test.html", $testPage);

// ! build pages
$pages = glob($theme_dir."page/*");

foreach($pages as $page){
    $file = pathinfo($page);

    $html = get_layout();
    $content = tags_parse(file_get_contents($page));
    $html = tag("content", $content, $html);
    $html = tag("title", ucwords($file['filename'])." | ", $html);
    $html = tags_parse($html);
    
    write_file("page/".$file['filename'], $html);
}

// ! generate posts
// $db->query("UPDATE entry SET published = NULL WHERE id = '3670';");
$result = $db->query("SELECT * FROM entry WHERE ".($rebuild ? "" : "published IS NULL AND ")."published_at < datetime('now');");
$changedTags = array();
while($entry = $result->fetchArray()){
    slog("new post {$entry['url_path']}");
    
    $html = get_layout();
    $html = tag("title", $entry['title']." | ", $html);
    
    $entry['published_at'] = format_date($entry['published_at']);
    
    $imageResult = $db->query("SELECT i.path as filename, k.path as dir, k.label, k.position FROM image i JOIN image_kind k ON i.kind = k.id  WHERE entry_id = {$entry['id']} ORDER BY position ASC;");
    $images = array();
    while($image = $imageResult->fetchArray()){
        $images[$image['dir']] = $image;
    }
    foreach($images as $image){
        if($image['position'] > 0){
            $entry['first_image'] = $baseurl."gallery/".$image['dir']."/".$image['filename'];
            break;
        }
    }
    $entry['preview'] = $baseurl."gallery/preview/".$images['preview']['filename'];
    $entry['url'] = $baseurl.$entry['url_path'];
    $kinds = "";
    foreach($images as $image){
        if($image['position'] > 0)
            $kinds .= "<li><a href='{$baseurl}gallery/{$image['dir']}/{$image['filename']}' title='{$image['label']}'>{$image['label']}</a></li>";
    }
    $entry['kinds'] = $kinds;
    $tagResult = $db->query("SELECT * FROM entry_tag e JOIN tag t ON e.tag_id = t.id WHERE entry_id = {$entry['id']};");
    $tags = "";
    while($tag = $tagResult->fetchArray()){
        $tags .= "<li><a href='{$baseurl}tag/{$tag['slug']}' title='{$tag['title']}'>{$tag['title']}</a></li>";
        $changedTags[] = $tag['slug'];
    }
    $entry['tags'] = $tags;
    
    $html = tag("head", tag_all("entry", $entry, file_get_contents($theme_dir."layout/entry_head.phtml")), $html);
    $html = tag("content", tag_all("entry", $entry, file_get_contents($theme_dir."layout/entry.phtml")), $html);
    
    $html = tags_parse($html);
    write_file($entry['url_path'], $html);
    $db->query("UPDATE entry SET published = 1 WHERE id = {$entry['id']};");
}

// ! build index
$html = get_layout(true);
$content = file_get_contents($theme_dir."index.phtml");
slog("rebuilding home page");
$entryResult = $db->query("SELECT * FROM entry WHERE published IS NOT NULL ORDER BY published_at DESC LIMIT 10;");
$entries = "";
$first = 0;
$entryLayout = file_get_contents($theme_dir."layout/entry_home.phtml");
while($entry = $entryResult->fetchArray()){

    
    $entry['published_at'] = format_date($entry['published_at']);
    $imageResult = $db->query("SELECT i.path as filename FROM image i WHERE entry_id = {$entry['id']} AND kind = 6;");
    $image = $imageResult->fetchArray();    
    $entry['preview'] = $baseurl."gallery/preview/".$image['filename'];
    
    if($first++ == 0) {
        $content = tag("entry_first", tag_all("entry", $entry, $entryLayout), $content);
    } else {
        $entries .= tag_all("entry", $entry, $entryLayout);
    }
}
$content = tag("entries", $entries, $content);

$html = tag("content", $content, $html);
$html = tags_parse($html);
file_put_contents($site_dir."index.html", $html);

// ! udpate tags
if($rebuild){
    $tagResult = $db->query("SELECT t.title, t.slug, t.id, count(*) as count, i.path AS thumb FROM tag t JOIN entry_tag e ON e.tag_id = t.id JOIN image i ON i.entry_id = e.entry_id WHERE list = 1 AND i.kind = 7 GROUP BY tag_id;");
} else {
    $tagResult = $db->query("SELECT t.title, t.slug, t.id, count(*) as count, i.path AS thumb FROM tag t JOIN entry_tag e ON e.tag_id = t.id JOIN image i ON i.entry_id = e.entry_id WHERE slug IN('".implode("','", $changedTags)."') AND list = 1 AND i.kind = 7 GROUP BY tag_id;");
}

// ! build tag pages
$html = get_layout();
$tagLayout = file_get_contents($theme_dir."layout/tag.phtml");
$columnCount = 3;
$i = 0;
while($tag = $tagResult->fetchArray()){
    $db->query("UPDATE tag SET count = ".$tag['count'].", thumb='".$tag['thumb']."' WHERE id = ".$tag['id'].";");
    
    $tagEntryResult = $db->query("SELECT title, url_path, published_at, i.path AS thumb FROM entry e JOIN image i ON i.entry_id = e.id JOIN entry_tag t ON t.entry_id = e.id WHERE i.kind = 7 AND t.tag_id = {$tag['id']} AND published IS NOT NULL ORDER BY published_at DESC;");
    $tagPage = '<div class="row entry-grid">';
    while($entry = $tagEntryResult->fetchArray()){
        $entry['count'] = format_date($entry['published_at'], true);
        $entry['thumb'] = $baseurl."gallery/thumb/".$entry['thumb'];
        $entry['slug'] = $baseurl . $entry['url_path'];
        $tagPage .= tag_all("tag", $entry, $tagLayout);
    }
    $tagPage .= '</div>';
    $tagHtml = get_layout();
    $tagHtml = tag("content", $tagPage, $tagHtml);
    $tagHtml = tag("title", $tag['title']." | ", $tagHtml);
    $tagHtml = tags_parse($tagHtml);
    write_file("tag/".$tag['slug'], $tagHtml);
    $i++;  
}
slog("updated $i tag pages");

// ! build tags index page
$html = get_layout(true);
$tagResult = $db->query("SELECT title, slug, count, thumb FROM tag t WHERE list = 1 AND count > 0 ORDER BY title ASC;");

$viewAll = '<div class="row entry-grid">';
while($tag = $tagResult->fetchArray()){
    $tag['count'] .= ($tag['count'] > 1) ? " entries" : " entry";
    $tag['thumb'] = $baseurl."gallery/thumb/".$tag['thumb'];
    $tag['slug'] = $baseurl . "tag/".$tag['slug'];
    $viewAll .= tag_all("tag", $tag, $tagLayout);
}
$viewAll .= '</div>';

$html = tag("content", $viewAll, $html);
$html = tag("title", "Tag Index | ", $html);
    $tagHtml = tag("content_title", "Wallpaper by Name", $tagHtml);
$html = tags_parse($html);
write_file("page/tags", $html);
slog("update tags index");

// ! do year tags
$year = date("Y");
$tagLayout = file_get_contents($theme_dir."layout/tag.phtml");
while($year >= 2000) {
    slog("update $year index");
    $tagEntryResult = $db->query("SELECT title, url_path, published_at, i.path as thumb FROM entry e JOIN image i ON i.entry_id = e.id WHERE i.kind = 7 AND published_at LIKE('{$year}%') AND published IS NOT NULL ORDER BY published_at DESC;");
    $tagPage = '<div class="row entry-grid">';
    while($entry = $tagEntryResult->fetchArray()){
        $entry['count'] = format_date($entry['published_at'], true);
        $entry['thumb'] = $baseurl."gallery/thumb/".$entry['thumb'];
        $entry['slug'] = $baseurl.$entry['url_path'];
        $tagPage .= tag_all("tag", $entry, $tagLayout);
    }
    $tagPage .= '</div>';
    $tagHtml = get_layout(true);
    $tagHtml = tag("content", $tagPage, $tagHtml);
    $tagHtml = tag("title", $year." | ", $tagHtml);
    $tagHtml = tag("content_title", "Wallpaper from {$year}", $tagHtml);
    $tagHtml = tags_parse($tagHtml);
    write_file("tag/".$year, $tagHtml);
    if(!$rebuild)
        break;
    $year--;
}

// ! do kind tags
$kindResult = $db->query("SELECT * FROM image_kind WHERE exclude != 1;");
while($kind = $kindResult->fetchArray()){
    if($kind['path'] == 'calendar') {
        $tagEntryResult = $db->query("SELECT title, url_path, published_at, i.path as thumb FROM entry e JOIN image i ON i.entry_id = e.id WHERE queue = 2 AND published IS NOT NULL GROUP BY e.id ORDER BY published_at DESC;");
    } else {
        $tagEntryResult = $db->query("SELECT title, url_path, published_at, i.path as thumb FROM entry e JOIN image i ON i.entry_id = e.id WHERE i.kind = {$kind['id']} AND published IS NOT NULL ORDER BY published_at DESC;");
    }
    $tagPage = '<div class="row entry-grid">';
    while($entry = $tagEntryResult->fetchArray()){
        $entry['count'] = format_date($entry['published_at'], true);
        $entry['thumb'] = $baseurl."gallery/thumb/".$entry['thumb'];
        $entry['slug'] = $baseurl . $entry['url_path'];
        $tagPage .= tag_all("tag", $entry, $tagLayout);
    }
    $tagPage .= '</div>';
    $tagHtml = get_layout(true);
    $tagHtml = tag("content", $tagPage, $tagHtml);
    $tagHtml = tag("title", $kind['label']." | ", $tagHtml);
    $tagHtml = tag("content_title", "Wallpaper with {$kind['label']} Version", $tagHtml);
    $tagHtml = tags_parse($tagHtml);
    write_file("tag/".$kind['path'], $tagHtml);   
    slog("update {$kind['path']} index"); 
}

// ! reports
$result = $db->query("SELECT count(*) as count FROM entry WHERE published IS NULL AND queue = 1;")->fetchArray();
if($result['count']){
    slog("Queued Normal Entries: {$result[count]}");
} else {
    slog("NO QUEUED ENTRIES REMAINING");
}
$result = $db->query("SELECT count(*) as count FROM entry WHERE published IS NULL AND queue = 2;")->fetchArray();
if($result['count']){
    slog("Queued Calendar Entries: {$result[count]}");
} else {
    slog("NO QUEUED CALENDAR ENTRIES REMAINING");
}

slog("done");














