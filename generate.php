<?php
/**
 * @author		Dennis Rogers
 * @address		www.drogers.net
 */
require_once("app.php");

if(strpos($base_dir, 'development'))
    $db->query("UPDATE entry SET published = null WHERE published IS NOT NULL ORDER BY id DESC LIMIT 5;");

slog('------------------');
slog('Start Regeneration');
if(isset($argv[1])){
    slog('Full Mode');
    $rebuild = true;
}

// ! flush site and copy assets
if($rebuild && is_dir($site_dir)){
    exec("rm {$site_dir}gallery");
    del_tree($site_dir);
}

if(!is_dir($site_dir))
    mkdir($site_dir, 0777);
    
if(!is_dir($site_dir."gallery"))
    exec("ln -s /var/www/spartacuswallpaper.com/gallery/ {$site_dir}gallery");
recurse_copy($assets_dir, $site_dir);
copy($assets_dir.'.htaccess', $site_dir.'.htaccess');

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
$result = $db->query("SELECT * FROM entry WHERE ".($rebuild ? "" : "published IS NULL AND ")."published_at < datetime('now');");
$changedTags = array();
while($entry = $result->fetchArray()){
    slog("new post {$entry['url_path']}");
    
    $html = get_layout();
    $html = tag("title", $entry['title']." | ", $html);
        
    $entry['published_at'] = format_date($entry['published_at']);
    $entry['preview'] = $baseurl."gallery/preview/".$entry['filename'];
    
    $imageResult = $db->query("SELECT i.path as filename, k.path as dir, k.label, k.position, k.mobile FROM image i JOIN image_kind k ON i.kind = k.id WHERE entry_id = {$entry['id']} AND exclude = 0 ORDER BY position ASC;");
    $images = array();
    while($image = $imageResult->fetchArray()){
        $images[$image['dir']] = $image;
    }
    $imageGallery = '';
    $hasMobile = false;
    foreach($images as $image){
        $imageUrl = $baseurl."gallery/".$image['dir']."/".$image['filename'];
        if($image['position'] == 1)
            $entry['first_image'] = $imageUrl;

        if($image['mobile']) {
            $hasMobile = true;
            $class = 'col-xs-6 col-sm-2';
        } else {
            $class = 'col-xs-12 col-sm-4 hidden-xs';
        }
        $imageGallery .= '<a href="'.$imageUrl.'" class="image '.$class.'" title="'.$entry['title'].'"><img src="'.$imageUrl.'" alt="'.$entry['title'].'"/><span>'.$image['label'].'</span></a>';
    }
    $entry['image_gallery'] = $imageGallery;
        
    $entry['url'] = $baseurl.$entry['url_path'];
    $kinds = "";
    foreach($images as $image){
        if($image['position'] > 0)
            $kinds .= "<li><a href='{$baseurl}gallery/{$image['dir']}/{$image['filename']}' title='{$image['label']}'>{$image['label']}</a></li>";
    }
    $entry['kinds'] = $kinds;
    $tagResult = $db->query("SELECT t.* FROM entry_tag e JOIN tag t ON e.tag_id = t.id WHERE entry_id = {$entry['id']} ORDER BY t.name DESC, t.title ASC;");
    $tags = '<h4>Subjects</h4><ul>';
    $tagIds = array();
    $switch = false;
    while($tag = $tagResult->fetchArray()){
        $tagIds[] = $tag['id'];
        if($switch == false && $tag['name'] == 0) {
            $switch = true;
            $tags .= '</ul><h4>Tags</h4><ul>';
        }
        $tags .= "<li><a href='{$baseurl}tag/{$tag['slug']}' title='{$tag['title']}'>{$tag['title']}</a></li>";
        $changedTags[] = $tag['slug'];
    }
    $tags .= '</ul>';
    $entry['tags'] = $tags;
    
    $excludeIds = array($entry['id']);
    $prev = $db->query('SELECT e.* FROM entry e JOIN entry o ON o.id = "'.$entry['id'].'" WHERE e.published_at < o.published_at ORDER BY e.published_at DESC LIMIT 1;')->fetchArray();
    if(isset($prev['id'])) {
        $entry['prev'] = '<a href="'.$baseurl.$prev['url_path'].'" title="'.$prev['title'].'"><span>Previous</span><img src="'.$baseurl.'gallery/preview/'.$prev['filename'].'" alt="'.$prev['title'].'" /><span>'.$prev['title'].'</span></a>';
        $entry['prev_link'] = '<a href="'.$baseurl.$prev['url_path'].'" title="'.$prev['title'].'"><span>&laquo; Previous Wallpaper</span></a>';
        $excludeIds[] = $prev['id'];
    }
    
    $next = $db->query('SELECT e.* FROM entry e JOIN entry o ON o.id = "'.$entry['id'].'" WHERE e.published_at > o.published_at ORDER BY e.published_at ASC LIMIT 1;')->fetchArray();
    if(isset($next['id'])) {
        $entry['next'] = '<a href="'.$baseurl.$next['url_path'].'" title="'.$next['title'].'"><span>Next</span><img src="'.$baseurl.'gallery/preview/'.$next['filename'].'" alt="'.$next['title'].'" /><span>'.$next['title'].'</span></a>';
        $entry['next_link'] = '<a href="'.$baseurl.$next['url_path'].'" title="'.$next['title'].'"><span>Next Wallpaper &raquo;</span></a>';
        $excludeIds[] = $next['id'];
    }
    
    $html = tag('content_more', getMore(6, $tagIds, 'col-sm-6 col-md-4', $excludeIds), $html);
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
$entryResult = $db->query("SELECT * FROM entry WHERE published IS NOT NULL ORDER BY published_at DESC LIMIT 200;");
$entries = "";
$entryLayout = file_get_contents($theme_dir."layout/entry_home.phtml");
$deskEntries = 0;
$mobileEntries = 0;
$homeEntryIds = array();
while($entry = $entryResult->fetchArray()){
    $entry['published_at'] = format_date($entry['published_at']);

    $imageResult = $db->query("SELECT k.path as dir, i.path as file, k.position FROM image i JOIN image_kind k ON k.id = i.kind WHERE entry_id = {$entry['id']} AND (k.mobile = 1 OR k.id = 6) ORDER BY k.position ASC LIMIT 3;");
    $mobileImages = '<div class="entry-images visible-xs">';
    $hasMobile = false;
    while($image = $imageResult->fetchArray()){
        if($image['dir'] == 'preview'){
            $entry['preview'] = $baseurl."gallery/".$image['dir']."/".$image['file'];
        } else {
            $hasMobile = true;
            $mobileImage = $baseurl."gallery/".$image['dir']."/".$image['file'];
            $mobileImages .= '<a href="'.$entry['url_path'].'" class="image col-xs-6" title="'.$entry['title'].'"><img src="'.$mobileImage.'" alt="'.$entry['title'].'"/></a>';
        }
    }
    $deskEntries++;
    if($hasMobile) {
        $entry['mobile_images'] = $mobileImages . '</div>';
        if($deskEntries > 10)
            $entry['mobile'] = 'visible-xs';
        else
            $entry['mobile'] = ''; 
        $mobileEntries++;
    } else {
        if($deskEntries > 10) continue;
        $entry['mobile'] = 'hidden-xs';
    }
    
    if($deskEntries > 10 && $mobileEntries > 10) break;
    $homeEntryIds[] = $entry['id'];
    if($deskEntries == 1) {
        $content = tag("entry_first", tag_all("entry", $entry, $entryLayout), $content);
    } else {
        $entries .= tag_all("entry", $entry, $entryLayout);
    }
}
$content = tag("entries", $entries, $content);

$html = tag("content", $content, $html);
$html = tag('side_more', getMore(7, null, 'col-sm-12', $homeEntryIds), $html);
$html = tags_parse($html);
file_put_contents($site_dir."index.html", $html);

// ! udpate tags
slog('updating tags');
if($rebuild){
    $tagResult = $db->query("SELECT t.title, t.slug, t.id, count(*) as count, i.path AS thumb FROM tag t JOIN entry_tag e ON e.tag_id = t.id JOIN image i ON i.entry_id = e.entry_id WHERE list = 1 AND i.kind = 7 GROUP BY tag_id;");
} else {
    $tagResult = $db->query("SELECT t.title, t.slug, t.id, count(*) as count, i.path AS thumb FROM tag t JOIN entry_tag e ON e.tag_id = t.id JOIN image i ON i.entry_id = e.entry_id WHERE slug IN('".implode("','", $changedTags)."') AND list = 1 AND i.kind = 7 GROUP BY tag_id;");
}

// ! build tag pages
slog('building tag pages');
$html = get_layout();
$tagLayout = file_get_contents($theme_dir."layout/tag.phtml");
$columnCount = 3;
$i = 0;
while($tag = $tagResult->fetchArray()){
    slog('tag page '.$tag['slug']);
    $db->query("UPDATE tag SET count = ".$tag['count'].", thumb='".$tag['thumb']."' WHERE id = ".$tag['id'].";");
    
    $tagEntryResult = $db->query("SELECT e.id, title, url_path, published_at FROM entry e JOIN entry_tag t ON t.entry_id = e.id WHERE t.tag_id = {$tag['id']} AND published IS NOT NULL ORDER BY published_at DESC;");
    $entries = $db->fetchAll($tagEntryResult);
    $tagPage = '<div class="row entry-grid">';
    foreach($entries as $entry) {
        $tagPage .= tag_entry($entry, $tagLayout, count($entries));
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

$tagResult = $db->query("SELECT * FROM tag t WHERE name = 1 AND count > 0 ORDER BY title ASC;");
$tagResult = $db->fetchAll($tagResult);
$tagResult = array_chunk($tagResult, ceil(count($tagResult) / 3));
$viewAll = '<h2>Wallpaper by Subject Name</h2>';
$viewAll .= '<div class="row">';
foreach($tagResult as $tagChunk) {
    $viewAll .= '<ul class="col-sm-4">';
    foreach($tagChunk as $tag) {
        $viewAll .= '<li><a href="'.$baseurl.'tag/'.$tag['slug'].'" title="Wallpaper of '.$tag['title'].'">'.$tag['title'].'</a></li>';
    }
    $viewAll .= '</ul>';
}
$viewAll .= '</div>';

$tagResult = $db->query("SELECT * FROM tag t WHERE name = 0 AND count > 0 ORDER BY title ASC;");
$tagResult = $db->fetchAll($tagResult);
$tagResult = array_chunk($tagResult, ceil(count($tagResult) / 3));
$viewAll .= '<h2>Other Tags</h2>';
$viewAll .= '<div class="row">';
foreach($tagResult as $tagChunk) {
    $viewAll .= '<ul class="col-sm-4">';
    foreach($tagChunk as $tag) {
        $viewAll .= '<li><a href="'.$baseurl.'tag/'.$tag['slug'].'" title="Wallpaper of '.$tag['title'].'">'.$tag['title'].'</a></li>';
    }
    $viewAll .= '</ul>';
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
    $tagEntryResult = $db->query("SELECT e.id, title, url_path, published_at FROM entry e WHERE published_at LIKE('{$year}%') AND published IS NOT NULL ORDER BY published_at DESC;");
    $entries = $db->fetchAll($tagEntryResult);
    $tagPage = '<div class="row entry-grid">';
    foreach($entries as $entry) {
        $tagPage .= tag_entry($entry, $tagLayout, count($entries));
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
        $tagEntryResult = $db->query("SELECT e.id, title, url_path, published_at, i.path as thumb FROM entry e JOIN image i ON i.entry_id = e.id WHERE queue = 2 AND published IS NOT NULL GROUP BY e.id ORDER BY published_at DESC;");
    } else {
        $tagEntryResult = $db->query("SELECT e.id, title, url_path, published_at, i.path as thumb FROM entry e JOIN image i ON i.entry_id = e.id WHERE i.kind = {$kind['id']} AND published IS NOT NULL ORDER BY published_at DESC;");
    }
    $entries = $db->fetchAll($tagEntryResult);
    $tagPage = '<div class="row entry-grid">';
    foreach($entries as $entry) {
        $tagPage .= tag_entry($entry, $tagLayout, count($entries));
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














