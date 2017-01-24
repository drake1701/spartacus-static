<?php
/**
 * @author		Dennis Rogers
 * @address		www.drogers.net
 */
require_once("app.php");

$dev = strpos($base_dir, 'development');

if($dev)
    $db->query("UPDATE entry SET published = null WHERE id IN(SELECT id from entry WHERE published IS NOT NULL ORDER BY published_at DESC LIMIT 2);");

slog('------------------');
slog('Start Regeneration');
$rebuild = false;
if(isset($argv[1])) {
    if($dev) {
        $mode = $argv[1];
        if($mode == 1) {
            slog('Full Mode');
            $dev = false;
            $rebuild = true;
        } else {
            slog('Mode '.$mode);
        }
    }
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
copy($assets_dir.'.htaccess.maint', $site_dir.'.htaccess');

// ! generate banner css
$banners = glob($assets_dir."images/banners/left/*.jpg");
$banners = array_merge($banners, glob($assets_dir."images/banners/right/*.jpg"));
$html = "";
foreach($banners as $i => $banner){
    $parts = array_reverse(explode("/", $banner));
    $file = $parts[0];
    $align = $parts[1];
    $imageUrl = $baseurl . 'images/banners/' . $align . '/' . $file;
    $topUrl = $baseurl . 'images/banners/top/' . str_replace('jpg', 'png', $file);
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
    	var counter = 0;
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
$testHtml .= str_repeat($header, (count($banners)-1));
$testPage = get_layout(0, 'empty');
$testPage = tag('content', $testHtml, $testPage);
$testPage = tags_parse($testPage);
$testPage = str_replace('col-md-9', 'col-md-12', $testPage);
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

// If posts are queued to be published, republish last entry, for next links
$result = $db->query("SELECT count(*) FROM entry WHERE ".($rebuild ? "" : "published IS NULL AND ")."published_at < datetime('now');");
$count = $result->fetchArray();
if($count[0] > 0) {
    $db->query("UPDATE entry SET published = null WHERE id = (SELECT id FROM entry WHERE published IS NOT NULL ORDER BY published_at DESC LIMIT 1);");
}

$result = $db->query("SELECT e.* FROM entry e WHERE ".($rebuild ? "" : "published IS NULL AND ")."published_at < datetime('now');");
$changedTags = array();
while($entry = $result->fetchArray()){
    slog("new post {$entry['url_path']}");
    
    $html = get_layout();
    $html = tag("title", $entry['title']." | ", $html);
        
    $entry['published_at'] = format_date($entry['published_at']);
    $entry['short_content'] = substr(strip_tags($entry['content']), 0, 45);
    if(strlen($entry['content']))
        $entry['content'] = '<p class="std">' . $entry['content'] . '</p>';
    
    $imageResult = $db->query("SELECT i.path as filename, k.path as dir, k.label, k.position, k.mobile FROM image i JOIN image_kind k ON i.kind = k.id WHERE entry_id = {$entry['id']} AND exclude = 0 ORDER BY position ASC;");
    $images = array();
    while($image = $imageResult->fetchArray()){
        $images[$image['dir']] = $image;
    }
    $imageGallery = '<div class="desktop hidden-xs">';
    $mobile = 0;
    foreach($images as $image){
        $imageUrl = $baseurl."gallery/".$image['dir']."/".$image['filename'];
        if(!isset($entry['first_image'])) {
            $entry['first_image'] = $imageUrl;
            $entry['preview'] = get_cache_url($imageUrl, 924);
        }

        if($image['mobile'] && !$mobile) {
            $imageGallery .= '</div><div class="mobile">';
            $mobile = true;
        }
        if($image['dir'] == 'ultrawide')
            $cache = get_cache_url($imageUrl, 450);
        else
            $cache = get_cache_url($imageUrl, 340);

        $imageGallery .= '<a property="hasPart" typeof="ImageObject" href="'.$imageUrl.'" class="image" title="'.$entry['title'].'"><meta property="url" content="'.$imageUrl.'"><img property="thumbnailUrl" src="'.$cache.'" alt="'.$entry['title'].'"/><span property="caption" content="'.$entry['title'].' - '.$image['label'].'">'.$image['label'].'</span></a>';
    }

    $imageGallery .= '</div>';
    $entry['image_gallery'] = $imageGallery;
        
    $entry['url'] = $baseurl.$entry['url_path'];
    $kinds = "";
    foreach($images as $image){
        if($image['position'] > 0)
            $kinds .= '<li property="hasPart" typeof="ImageObject"><a href="'.$baseurl.'gallery/'.$image['dir'].'/'.$image['filename'].'" title="'.$image['label'].'">'.$image['label'].'</a></li>';
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
        $entry['prev'] = '<a href="'.$baseurl.$prev['url_path'].'" title="'.$prev['title'].'"><span>Previous</span><img src="'.get_cache_url($prev['thumb'], 360).'" alt="'.$prev['title'].'" /><span>'.$prev['title'].'</span></a>';
        $entry['prev_link'] = '<a href="'.$baseurl.$prev['url_path'].'" title="'.$prev['title'].'"><span>&laquo; Previous Wallpaper</span></a>';
        $excludeIds[] = $prev['id'];
    }
    
    $next = $db->query('SELECT e.* FROM entry e JOIN entry o ON o.id = "'.$entry['id'].'" WHERE e.published_at > o.published_at ORDER BY e.published_at ASC LIMIT 1;')->fetchArray();
    if(isset($next['id'])) {
        $nexturl = $baseurl.$next['url_path'];
        $nextImg = '<span>Next</span><img src="'.get_cache_url($next['thumb'], 360).'" alt="'.$next['title'].'" /><span>'.$next['title'].'</span>';
        
        $entry['next'] = $next['published'] ? 
            '<a href="'.$nexturl.'" title="'.$next['title'].'">'.$nextImg.'</a>' :
            $nextImg;
            
        $entry['next_link'] = $next['published'] ? '<a href="'.$nexturl.'" title="'.$next['title'].'"><span>Next Wallpaper &raquo;</span></a>' : '';
        $excludeIds[] = $next['id'];
    }
    
    $html = tag('content_more', getMore(6, $tagIds, 'col-sm-4', $excludeIds), $html);
    $html = tag("head", tag_all("entry", $entry, file_get_contents($theme_dir."layout/entry_head.phtml")), $html);
    $html = tag("content", tag_all("entry", $entry, file_get_contents($theme_dir."layout/entry.phtml")), $html);
    
    $html = tags_parse($html);
    write_file($entry['url_path'], $html);
    $db->query("UPDATE entry SET published = 1 WHERE id = {$entry['id']};");
}

if(!$dev || $mode == 't') {
// ! update tags
    slog('updating tags');
    if ($rebuild) {
        $tagResult = $db->query("SELECT t.title, t.slug, t.id, count(*) AS count FROM tag t JOIN entry_tag e ON e.tag_id = t.id WHERE list = 1 GROUP BY tag_id;");
    } else {
        $tagResult = $db->query("SELECT t.title, t.slug, t.id, count(*) AS count FROM tag t JOIN entry_tag e ON e.tag_id = t.id WHERE slug IN('" . implode("','", $changedTags) . "') AND list = 1 GROUP BY tag_id;");
    }

// ! build tag pages
    slog('building tag pages');
    $html = get_layout();
    $tagLayout = file_get_contents($theme_dir . "layout/tag.phtml");
    $columnCount = 3;
    $tagCount = 0;
    while ($tag = $tagResult->fetchArray()) {
        slog('tag page ' . $tag['slug']);
        $db->query("UPDATE tag SET count = " . $tag['count'] . ", thumb='" . $tag['thumb'] . "' WHERE id = " . $tag['id'] . ";");

        $tagEntryResult = $db->query("SELECT e.id, title, url_path, published_at FROM entry e JOIN entry_tag t ON t.entry_id = e.id WHERE t.tag_id = {$tag['id']} AND published IS NOT NULL ORDER BY published_at DESC;");
        $entries = $db->fetchAll($tagEntryResult);
        $entryPages = array_chunk($entries, $page_size);

        foreach ($entryPages as $page => $pageEntries) {
            $page += 1;

            $tagPage = '<div class="row entry-grid">';
            $count = count($entryPages) > 1 ? $page_size : count($pageEntries);
            foreach ($pageEntries as $entry) {
                $tagPage .= tag_entry($entry, $tagLayout, $count);
            }
            $tagPage .= '</div>';

            $tagHtml = get_layout();
            if ($page > 1)
                $tagPage = '<button class="btn btn-home btn-lg pull-right"><a href="' . $baseurl . 'tag/' . $tag['slug'] . '">Newest Entries</a></button><div class="clearfix"></div>' . $tagPage;
            $tagHtml = tag("content", $tagPage, $tagHtml);
            $tagHtml = tag('pager', pager('tag/' . $tag['slug'], $page, count($entryPages)), $tagHtml);
            $tagHtml = tag("title", $tag['title'] . ' | ', $tagHtml);
            $tagHtml = tag("content_title", $tag['title'] . ' Wallpaper', $tagHtml);
            $tagHtml = tags_parse($tagHtml);
            write_file('tag/' . $tag['slug'] . '/page/' . $page, $tagHtml);
            if ($page == 1)
                write_file("tag/" . $tag['slug'], $tagHtml);
        }
        $tagCount++;
    }
    slog('updated ' . $tagCount . ' tag pages');


// ! build tags index page
    $html = get_layout(true);
    $tagResult = $db->query("SELECT * FROM tag t WHERE name = 1 AND count > 0 ORDER BY title ASC;");
    $tagResult = $db->fetchAll($tagResult);
    $tagResult = array_chunk($tagResult, ceil(count($tagResult) / 3));
    $viewAll = '<h2>Wallpaper by Subject Name</h2>';
    $viewAll .= '<div class="row">';
    foreach ($tagResult as $tagChunk) {
        $viewAll .= '<ul class="col-sm-4">';
        foreach ($tagChunk as $tag) {
            $viewAll .= '<li><a href="' . $baseurl . 'tag/' . $tag['slug'] . '" title="Wallpaper of ' . $tag['title'] . '">' . $tag['title'] . '</a></li>';
        }
        $viewAll .= '</ul>';
    }
    $viewAll .= '</div>';

    $tagResult = $db->query("SELECT * FROM tag t WHERE name = 0 AND count > 0 ORDER BY title ASC;");
    $tagResult = $db->fetchAll($tagResult);
    $tagResult = array_chunk($tagResult, ceil(count($tagResult) / 3));
    $viewAll .= '<h2>Other Tags</h2>';
    $viewAll .= '<div class="row">';
    foreach ($tagResult as $tagChunk) {
        $viewAll .= '<ul class="col-sm-4">';
        foreach ($tagChunk as $tag) {
            $viewAll .= '<li><a href="' . $baseurl . 'tag/' . $tag['slug'] . '" title="Wallpaper of ' . $tag['title'] . '">' . $tag['title'] . '</a></li>';
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
}

if(!$dev || $mode == 'y') {
// ! do year tags
    $year = date("Y");
    $tagLayout = file_get_contents($theme_dir . "layout/tag.phtml");
    while ($year >= 2000) {
        slog("update $year index");
        $tagEntryResult = $db->query("SELECT e.id, title, url_path, published_at FROM entry e WHERE published_at LIKE('{$year}%') AND published IS NOT NULL ORDER BY published_at DESC;");
        $entries = $db->fetchAll($tagEntryResult);
        $entryPages = array_chunk($entries, $page_size);

        foreach ($entryPages as $page => $pageEntries) {
            $page += 1;

            $tagPage = '<div class="row entry-grid">';
            foreach ($pageEntries as $entry) {
                $tagPage .= tag_entry($entry, $tagLayout, $page_size);
            }
            $tagPage .= '</div>';

            $tagHtml = get_layout();
            if ($page > 1)
                $tagPage = '<button class="btn btn-home btn-lg pull-right"><a href="' . $baseurl . 'tag/' . $year . '">Newest Entries</a></button><div class="clearfix"></div>' . $tagPage;
            $tagHtml = tag("content", $tagPage, $tagHtml);
            $tagHtml = tag('pager', pager('tag/' . $year, $page, count($entryPages)), $tagHtml);
            $tagHtml = tag("title", $year . " | ", $tagHtml);
            $tagHtml = tag("content_title", "Wallpaper from {$year}", $tagHtml);
            $tagHtml = tags_parse($tagHtml);
            write_file('tag/' . $year . '/page/' . $page, $tagHtml);
            if ($page == 1)
                write_file("tag/" . $year, $tagHtml);
        }
        if (!$rebuild)
            break;
        $year--;
    }
}

if(!$dev || $mode == 'k') {
// ! do kind tags
    $kindResult = $db->query("SELECT * FROM image_kind WHERE exclude != 1;");
    while ($kind = $kindResult->fetchArray()) {
        if ($kind['path'] == 'calendar') {
            $tagEntryResult = $db->query("SELECT e.id, title, url_path, published_at FROM entry e WHERE queue = 2 AND published IS NOT NULL GROUP BY e.id ORDER BY published_at DESC;");
        } else {
            $tagEntryResult = $db->query("SELECT e.id, title, url_path, published_at, k.path || '/' || i.path as thumb FROM entry e JOIN image i ON i.entry_id = e.id JOIN image_kind k ON i.kind = k.id WHERE i.kind = {$kind['id']} AND published IS NOT NULL ORDER BY published_at DESC;");
        }
        $entries = $db->fetchAll($tagEntryResult);
        $entryPages = array_chunk($entries, $page_size);

        $class = $kind['mobile'] ? 'col-md-4 col-sm-3 col-xs-6' : 'col-md-6 col-sm-4 col-xs-12';

        foreach ($entryPages as $page => $pageEntries) {
            $page += 1;

            $tagPage = '<div class="row entry-grid">';
            foreach ($pageEntries as $entry) {
                $tagPage .= tag_entry($entry, $tagLayout, $page_size, 'tag', $class, false);
            }
            $tagPage .= '</div>';

            $tagHtml = get_layout();
            if ($page > 1)
                $tagPage = '<button class="btn btn-home btn-lg pull-right"><a href="' . $baseurl . 'tag/' . $kind['path'] . '">Newest Entries</a></button><div class="clearfix"></div>' . $tagPage;
            $tagHtml = tag("content", $tagPage, $tagHtml);
            $tagHtml = tag('pager', pager('tag/' . $kind['path'], $page, count($entryPages)), $tagHtml);
            $tagHtml = tag("title", $kind['path'] . " | ", $tagHtml);
            $tagHtml = tag("content_title", "Wallpaper with {$kind['label']} Version", $tagHtml);
            $tagHtml = tags_parse($tagHtml);
            write_file('tag/' . $kind['path'] . '/page/' . $page, $tagHtml);
            if ($page == 1)
                write_file("tag/" . $kind['path'], $tagHtml);
        }
        slog("update {$kind['path']} index");
    }
}

// ! Changelog pages
$year = date("Y");
while($year >= 2000) {
    
    $changes = array();
    
    $entries = $db->query("SELECT e.id, title, url_path, published_at FROM entry e WHERE published_at LIKE('{$year}%') AND published IS NOT NULL ORDER BY published_at DESC;");
    while($entry = $entries->fetchArray()) {
        $changes[date('Y-m-d', strtotime($entry['published_at']))][] = array(
            'date' => $entry['published_at'],
            'message' => '<a href="'.$baseurl.$entry['url_path'].'">'.$entry['title'].'</a> published.'
        );
    }
    
    $logs = $db->query("SELECT DISTINCT date(l.created_at) AS created_at, l.message, e.title, e.url_path FROM entry_log l JOIN entry e ON l.entry_id = e.id WHERE l.created_at LIKE('{$year}%') AND e.published IS NOT NULL ORDER BY created_at DESC, e.title ASC;");
    while($log = $logs->fetchArray()) {
        $changes[$log['created_at']][] = array(
            'date' => $log['created_at'],
            'message' => '<a href="'.$baseurl.$log['url_path'].'">'.$log['title'].'</a> '.$log['message']
        );
    }
    
    krsort($changes);
    
    $page = '<dl>';
    foreach($changes as $date => $change) {
        $page .= '<dt>'.format_date($date, 1).'</dt>';
        $page .= '<dd><ul>';
        foreach($change as $message) {
            $page .= '<li>'.$message['message'].'</li>';
        }
        $page .= '</ul></dd>';
    }
    $page .= '</dl>';
    
    for($y = date('Y'); $y >= 2000; $y--) {
        $page .= '<a href="'.$baseurl.'changelog/'.$y.'">'.$y.'</a>&nbsp;&nbsp;';
    }
    
    
    $html = get_layout(true);
    $html = tag("content", $page, $html);
    $html = tag("title", "Changelog, $year | ", $html);
    $html = tag("content_title", "Changelog $year", $html);
    $html = tags_parse($html);
    write_file('changelog/'.$year, $html); 
    if($year == date('Y'))
        write_file('changelog', $html); 
    
    slog("update changelog $year");

    if(!$rebuild)
        break;
    $year--;
}

// ! build index
slog("rebuilding home page");

$layout = get_layout(true);
$entryLayout = file_get_contents($theme_dir . "layout/entry_home.phtml");

$entryResult = $db->query("SELECT * FROM entry WHERE published IS NOT NULL ORDER BY published_at DESC;");
$entries = $db->fetchAll($entryResult);
$entryPages = array_chunk($entries, 10);

$first = 0;

foreach ($entryPages as $page => $pageEntries) {
    $page += 1;

    if ($page > 1)
        $entriesHtml = '<button class="btn btn-home btn-lg pull-right"><a href="' . $baseurl . '">Newest Entries</a></button><div class="clearfix"></div>';
    else
        $entriesHtml = '';

    foreach ($pageEntries as $index => $entry) {

        $entry['preview'] = get_cache_url($entry['thumb'], 924);
        if (strlen($entry['content']))
            $entry['content'] = '<p class="std">' . $entry['content'] . '</p>';


        $imageResult = $db->query("SELECT k.path as dir, i.path as file, k.position FROM image i JOIN image_kind k ON k.id = i.kind WHERE entry_id = {$entry['id']} AND k.mobile = 1 ORDER BY k.position ASC LIMIT 2;");
        $mobileImages = '<div class="entry-images visible-xs">';
        $hasMobile = false;
        while ($image = $imageResult->fetchArray()) {
            $hasMobile = true;
            $mobileImage = $baseurl . "gallery/" . $image['dir'] . "/" . $image['file'];
            $mobileImages .= '<a href="' . $baseurl . $entry['url_path'] . '" class="image col-xs-6" title="' . $entry['title'] . '"><img src="' . get_cache_url($mobileImage, 340) . '" alt="' . $entry['title'] . '"/></a>';
        }

        $entry['published_at'] = format_date($entry['published_at']);

        if ($hasMobile) {
            $entry['mobile_images'] = $mobileImages . '</div>';
            $entry['mobile'] = 'hidden-xs';
        } else {
            $entry['mobile'] = '';
        }

        $entriesHtml .= tag_all("entry", $entry, $entryLayout);
        if ($index == 0) {
            $entriesHtml .= "{{include ad-middle.phtml}}";
        }

    }

    $html = tag("content", $entriesHtml, $layout);
    $html = tag('side_more', getMore(7, null, 'col-sm-12', $homeEntryIds), $html);
    $html = tag('pager', pager('', $page, count($entryPages)), $html);
    $html = tags_parse($html);

    write_file('page/' . $page, $html);
    if ($page == 1) {
        write_file("index.html", $html);
        if ($dev || $mode != 'h') {
            break;
        }
    }

}

$result = $db->query("SELECT e.* FROM entry e WHERE published IS NOT NULL;");

$sitemap = new \XMLWriter();
$sitemap->openURI($site_dir.'sitemap.xml');
$sitemap->startElement('urlset');
$sitemap->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$sitemap->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
while($entry = $result->fetchArray()) {
    $sitemap->startElement('url');

    $sitemap->writeElement('loc', $baseurl.$entry['url_path']);

    /** @var SQLite3Stmt $imageResults */
    $imageResults = $db->prepare("SELECT i.path as filename, k.path as dir, k.label, k.position, k.mobile FROM image i JOIN image_kind k ON i.kind = k.id WHERE entry_id = :entry_id AND exclude = 0 ORDER BY position ASC;");
    $imageResults->bindValue(':entry_id', $entry['id']);
    $images = $imageResults->execute();

    while($image = $images->fetchArray()) {
        $sitemap->startElement('image:image');
        $sitemap->writeElement('image:loc', $baseurl."gallery/".$image['dir']."/".$image['filename']);
        $sitemap->endElement();
    }

    $sitemap->endElement();
}
$sitemap->endElement();
$sitemap->flush();
slog('Sitemap');

if($dev)
    copy($assets_dir.'.htaccess.dev', $site_dir.'.htaccess');
else
    copy($assets_dir.'.htaccess', $site_dir.'.htaccess');


// ! reports
$result = $db->query("SELECT count(*) as count FROM entry WHERE published IS NULL AND queue = 1;")->fetchArray();
if($result['count']){
    slog("Queued Normal Entries: {$result['count']}");
} else {
    slog("NO QUEUED ENTRIES REMAINING");
}
$result = $db->query("SELECT count(*) as count FROM entry WHERE published IS NULL AND queue = 2;")->fetchArray();
if($result['count']){
    slog("Queued Calendar Entries: {$result['count']}");
} else {
    slog("NO QUEUED CALENDAR ENTRIES REMAINING");
}

slog("done");














