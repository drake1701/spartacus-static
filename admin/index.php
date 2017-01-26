<?php
/**
 * @author		Dennis Rogers
 * @address		www.drogers.net
 */

session_start();
require_once("../app.php");
require_once("../queue.php");
$queue = new Queue();
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Spartacus Admin</title>
    <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script type="text/javascript">
    jQuery(function() {
        jQuery( ".calendar" ).sortable({
            stop: function() {
                jQuery('#calendar').submit();
            },
            items:'.item'
        });
        jQuery( ".calendar" ).disableSelection();
        jQuery('.entry img').click(function(){
            jQuery(this).parent().toggleClass('ui-selected');
            var check = jQuery(this).parent().find('input[type=checkbox]');
            check.prop('checked', check.prop('checked') ? '' : 'checked');
        });
		var availableTags = [
			<?php 
    			$tags = $db->query('SELECT slug FROM tag t;'); 
    			while($tag = $tags->fetchArray()) {
        			echo '"'.$tag['slug'].'", ';
    			}
			?>
		];
		function split( val ) {
			return val.split( /,\s*/ );
		}
		function extractLast( term ) {
			return split( term ).pop();
		}

		$( ".tags" )
			// don't navigate away from the field on tab when selecting an item
			.bind( "keydown", function( event ) {
				if ( event.keyCode === $.ui.keyCode.TAB &&
						$( this ).autocomplete( "instance" ).menu.active ) {
					event.preventDefault();
				}
			})
			.autocomplete({
				minLength: 0,
				source: function( request, response ) {
					// delegate back to autocomplete, but extract the last term
					response( $.ui.autocomplete.filter(
						availableTags, extractLast( request.term ) ) );
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui ) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push( "" );
					this.value = terms.join( ", " );
					return false;
				},
				appendTo: '#tags-element'
			});
    });
    jQuery(window).load(function(){
        var height = 0;
        jQuery('.calendar > div').each(function(){
            if(jQuery(this).height() > height) {
                height = jQuery(this).height();
            }
        });
        jQuery('.calendar > div').height('25px');
        jQuery('.calendar > div:gt(6)').height(height);  
        jQuery('.calendar > div:nth-child(7n+1)').css({
            clear:'left',
            borderLeft: '1px solid #A4B070'
        });
    });
    </script>
    <link href="<?php echo $baseurl ?>css/global.css" media="screen" rel="stylesheet" type="text/css" />
    <link href="<?php echo $baseurl ?>css/bootstrap.min.css" media="screen" rel="stylesheet" type="text/css" />
    <link href="<?php echo $baseurl ?>css/custom.css" media="screen" rel="stylesheet" type="text/css" />
    <style type="text/css">
        .ui-selected, .not-name { background-color: #4A5404; }
        .ui-helper-hidden-accessible {display:none;}
        .ui-autocomplete { background-color: #282F02; }
        #tags-element {width:333px;}
        .calendar {
            clear:both;
            overflow:auto;
            width:100%;
            border-bottom: 1px solid #A4B070;
        }
        .calendar > div {
            width: 5%;
            vertical-align: top;
            float:left;
            border: 1px solid rgb(222,209,167);
            border-width: 1px 1px 0 0;
            padding: 0 5px;
        }
        .calendar > div:nth-child(7n-5), .calendar > div:nth-child(7n-2), .calendar > div:nth-child(7n) {
            width: 25%;
        }
        .calendar > div:nth-child(7n-6) {
            width: 10%;
        }
        .calendar.reposts > div {
            width:14%;
        }
        .calendar .entry-title {
            font-size:.9em;
        }
        .calendar > div.live {
            opacity:.3;
        }
    </style>
</head>
<body>
<br/>
<div class="container" id="main">
    <?php require_once __DIR__ . '/../access.php' ?>
<?php if(!empty($_GET['action'])): ?>
<?php switch($_GET['action']): 
          case "edit": ?>
    <?php case "newprocess": ?>
    <?php /* !new and edit form */ ?>
    <?php 
    if(!empty($_GET['id'])){
        $id = (Integer)$_GET['id'];
        $entry = $db->query("SELECT * FROM entry WHERE id = $id LIMIT 1;")->fetchArray();
        $tags = $db->query("SELECT GROUP_CONCAT(slug) as tags FROM tag t JOIN entry_tag e on e.tag_id = t.id WHERE e.entry_id = $id;")->fetchArray();
        $entry['tags'] = $tags['tags'];
    } else {
        $image = $_GET['image'];
        $entry = array(
            "id" => null,
            "title" => codeToName(str_replace(".jpg", '', strtolower($image))),
            "filename" => $image,
            "thumb" => 'widescreen/'.$image,
            "content" => null,
            "tags" => preg_replace("#-[ivxlm]*\$#", "", str_replace(".jpg", '', strtolower($image))),
            "url_path" => str_replace(".jpg", ".html", $image),
        );
    }
    ?>
    <h1>Edit</h1>
    <a href="#" class="col-xs-6 pull-right" onclick="window.open('<?php echo $baseurl ?>gallery/<?php echo $entry['thumb'] ?>', '','toolbar=no, scrollbars=yes, resizable=yes, top=10, left=10, width=960, height=600');">
	    <img src="<?php echo get_cache_url($entry['thumb'], 924) ?>">
    </a>
<form enctype="application/x-www-form-urlencoded" action="?action=save" method="post">
    <dl class="zend_form">
        <?php if($entry['published_at']): ?>
            <dd><?php echo format_date($entry['published_at']) ?></dd>
        <?php endif; ?>
        <dt id="title-label"><label for="title" class="required">title:</label></dt>
        <dd id="title-element">
            <input type="text" name="title" id="title" value="<?php echo $entry['title'] ?>">
        </dd>
        <dt id="content-label"><label for="content" class="optional">content:</label></dt>
        <dd id="content-element">
            <textarea name="content" id="content" cols="40" rows="5"><?php echo $entry['content'] ?></textarea>
        </dd>
        <dt id="queue-label"><label for="queue" class="optional">queue:</label></dt>
        <dd id="queue-element">
        <select name="queue" id="queue">
            <option value="1" <?php if($entry['queue'] == 1) echo 'selected="selected" ' ?>label="Normal">Normal</option>
            <option value="2" <?php if($entry['queue'] == 2) echo 'selected="selected" ' ?>label="Calendar">Calendar</option>
        </select></dd>
        <dt id="tags-label"><label for="tags" class="required">tags:</label></dt>
        <dd id="tags-element">
            <textarea class="tags" name="tags" id="tags" cols="40" rows="5"><?php echo $entry['tags'] ?></textarea>
        </dd>
        <dt id="url_path-label"><label for="url_path" class="required">url path:</label></dt>
        <dd id="url_path-element">
        <input type="text" name="url_path" id="url_path" value="<?php echo $entry['url_path'] ?>">
        </dd>
        <dt id="filename-label">&nbsp;</dt>
        <dd id="filename-element">
            <input type="hidden" name="filename" value="<?php echo $entry['filename'] ?>" id="filename">
            <input type="hidden" name="thumb" value="<?php echo $entry['thumb'] ?>" id="thumb">
            <input type="hidden" name="id" value="<?php echo $entry['id'] ?>" id="id">
        </dd>
        <dt id="save-label">&nbsp;</dt>
        <dd id="save-element">
            <input type="submit" value="save">
        </dd>
    </dl>
</form> 
<div style="clear:both;"></div>
    <?php break; ?>
    <?php
    /* ! entry saving */
    case "save":
    $data = $_POST;
    $slugs = explode(",", $data['tags']);
    unset($data['tags']);

    if($data['id'] > 0){
        $query = "UPDATE entry SET ";
        $data['modified_at'] = date('Y-m-d H:i:s');
        $data['published'] = NULL;
        foreach($data as $key => $value){
            $query .= "$key = :$key, ";
        }
        $query = trim($query, ", ");
        $query .= " WHERE id = :id;";
        $update = $db->prepare($query);
        foreach($data as $key => $value){
            $update->bindValue(":$key", $value);
        }
        execute($update);
        elog('Updated.', $data['id']);
        echo "<h2><em>Entry {$data['id']} saved successfully.</em></h2>";
    } else {
        /* ! entry creation */
        $data['created_at'] = $data['modified_at'] = date('Y-m-d H:i:s');
        $data['published_at'] = date('Y-m-d H:i:s', strtotime($queue->getLastQueuedDate($data['queue'])));        
        unset($data['id']);
        $query = "INSERT INTO entry(".implode(", ", array_keys($data)).") VALUES (";
        foreach($data as $key => $value){
            $query .= ":$key, ";
        }
        $query = trim($query, ", ");
        $query .= ");";
        $insert = $db->prepare($query);
        foreach($data as $key => $value){
            $insert->bindValue(":$key", $value);
        }
        execute($insert);
        $data['id'] = $db->lastInsertRowID();
        echo "<h2><em>Entry {$data['id']} created successfully.</em></h2>";
        
    }
    // !handle tags
	// Current & New
	foreach($slugs as $slugKey => $slug){
		$slug = str_replace(' ', '-', trim(strtolower($slug)));
		$slugs[$slugKey] = $slug;
		if($slug == '') continue;
		$tag = $db->prepare("SELECT * FROM tag WHERE slug = :slug;");
		$tag->bindParam(":slug", $slug);
		$tag = $tag->execute();
		$tag = $tag->fetchArray();
		if($tag['id'] == false){
		    $ins = $db->prepare("INSERT INTO tag (title, slug) VALUES (:title, :slug);");
		    $ins->bindValue(":title", codeToName($slug));
		    $ins->bindValue(":slug", $slug);
		    execute($ins);
            echo "<h3><em>Created new tag '".codeToName($slug)."'.</em></h3>";
		    $tag['id'] = $db->lastInsertRowID();
		}
		echo "<p>".$slug.' - '.$tag['id']."</p>";
		
		$linkResult = $db->prepare("SELECT * FROM entry_tag e WHERE entry_id = :entry_id AND tag_id = :tag_id;");
		$linkResult->bindParam(":entry_id", $data['id']);
		$linkResult->bindParam(":tag_id", $tag['id']);
		$linkResult = $linkResult->execute();
		$link = $linkResult->fetchArray();
		if($link['id'] == false) {
		    $ins = $db->prepare("INSERT INTO entry_tag (entry_id, tag_id) VALUES (:entry_id, :tag_id);");
		    $ins->bindValue(":tag_id", $tag['id']);
		    $ins->bindValue(":entry_id", $data['id']);
		    execute($ins);
		}
	}
	// deletes
	$linkResult = $db->prepare("SELECT e.id, t.slug FROM entry_tag e JOIN tag t ON t.id = e.tag_id WHERE entry_id = :entry_id;");
	$linkResult->bindParam(":entry_id", $data['id']);
	$linkResult = $linkResult->execute();
	while($link = $linkResult->fetchArray()){
		if(!in_array($link['slug'], $slugs)){
		    $del = $db->prepare("DELETE FROM entry_tag WHERE id = :id;");
		    $del->bindParam(":id", $link['id']);
		    execute($del);
		}
	}
	// !handle images
    $imageResult = $db->prepare("SELECT i.path as filename, k.path as dir, k.label, k.position FROM image i JOIN image_kind k ON i.kind = k.id  WHERE entry_id = :entry_id;");
    $imageResult->bindParam(":entry_id", $data['id']);
    $imageResult = $imageResult->execute();
    $images = array();
    while($image = $imageResult->fetchArray()){
        $images[$image['dir']] = $image;
    }
	
	$kinds = $db->query("select * from image_kind;");
    while($kind = $kinds->fetchArray()){
        if(isset($images[$kind['path']])) continue;
        if(file_exists($site_dir."gallery/{$kind['path']}/{$data['filename']}")){
            $ins = $db->prepare("INSERT INTO image (entry_id, path, kind) VALUES (:entry_id, :path, :kind);");
            $ins->bindValue(":entry_id", $data['id']);
            $ins->bindValue(":path", $data['filename']);
            $ins->bindValue(":kind", $kind['id']);
            execute($ins);
        }
    }
    break; ?>
    <?php case 'reorder': 
        /** !reordering */
        $data = $_POST;
        foreach($data['entry_id'] as $queueId => $postIds) {
            $queueDate = $queue->getLastPublishedDate($queueId);
            foreach($postIds as $postId) {
                $query = "UPDATE entry SET ";
                $data = array();
                $data['id'] = $postId;
                $data['modified_at'] = date('Y-m-d H:i:s');
                $data['published_at'] = date('Y-m-d H:i:s', strtotime($queue->getNext($queueDate, $queueId)));
                $queueDate = $queue->getNext($queueDate, $queueId);
                foreach($data as $key => $value){
                    $query .= "$key = :$key, ";
                }
                $query = trim($query, ", ");
                $query .= " WHERE id = :id;";
                $update = $db->prepare($query);
                foreach($data as $key => $value){
                    $update->bindValue(":$key", $value);
                }
                execute($update);
            }
        }
    echo '<h2><em>Reordering Successful.</em></h2>';
    break;
    case 'delete':
    /* !deleting */
    if($_GET['id']){
        $query = "DELETE FROM entry WHERE id = :id;";
        $delete = $db->prepare($query);
        $delete->bindValue(":id", $_GET['id']);
        execute($delete);
        echo '<h2><em>Deleted</em></h2>';
    }
    break;
    case 'showall':
 
    case 'addtag':
        /* !mass add tags */
        if(!empty($_POST['tags']) && is_array($_POST['entry'])) {
            $slugs = explode(',', $_POST['tags']);
            foreach($slugs as $slug) {
        		$slug = str_replace(' ', '-', trim(strtolower($slug)));
        		if($slug == '') continue;
        		$tag = $db->prepare("SELECT * FROM tag WHERE slug = :slug;");
        		$tag->bindParam(":slug", $slug);
        		$tag = $tag->execute();
        		$tag = $tag->fetchArray();
        		if($tag['id'] == false){
        		    $ins = $db->prepare("INSERT INTO tag (title, slug) VALUES (:title, :slug);");
        		    $ins->bindValue(":title", codeToName($slug));
        		    $ins->bindValue(":slug", $slug);
        		    execute($ins);
                    echo "<h3><em>Created new tag '".codeToName($slug)."'.</em></h3>";
        		    $tag['id'] = $db->lastInsertRowID();
        		}
        		foreach($_POST['entry'] as $entryId => $flag) {
            		$linkResult = $db->prepare("SELECT * FROM entry_tag e WHERE entry_id = :entry_id AND tag_id = :tag_id;");
            		$linkResult->bindParam(":entry_id", $entryId);
            		$linkResult->bindParam(":tag_id", $tag['id']);
            		$linkResult = $linkResult->execute();
            		$link = $linkResult->fetchArray();
            		if($link['id'] == false) {
            		    $ins = $db->prepare("INSERT INTO entry_tag (entry_id, tag_id) VALUES (:entry_id, :tag_id);");
            		    $ins->bindValue(":tag_id", $tag['id']);
            		    $ins->bindValue(":entry_id", $entryId);
            		    execute($ins);
            		    $flag = $db->prepare("UPDATE entry SET published = NULL, modified_at = datetime('now') WHERE id = :id;");
            		    $flag->bindParam(':id', $entryId);
            		    execute($flag);
            		    elog('Added tags.', $entryId);
            		    echo "<p>Added $entryId to $slug</p>";
            		}    		
        		}
    		}
        }
        /* !show all */
        echo '<h1><a href="/">All Posts</a></h1>';
        $sql = 'SELECT e.*, group_concat(t.slug) AS tags FROM entry e JOIN entry_tag et ON et.entry_id = e.id JOIN tag t ON t.id = et.tag_id';
        if(!empty($_GET['tag'])) {
            $sql .= ' JOIN entry_tag fet ON fet.entry_id = e.id JOIN tag ft ON ft.id = fet.tag_id WHERE ft.slug = "'.$_GET['tag'].'"';
        }
        $sql .= ' GROUP BY et.entry_id ORDER BY published_at DESC;';
        $result = $db->query($sql);
        ?>
        <form action="?<?php echo http_build_query($_GET) ?>" method="post">
            <div class="row entry-grid">
            <?php $i = 0; ?>
            <?php while($entry = $result->fetchArray()): ?>
            	<div class="col-xs-3 entry">
                	<input class="no-display" type="checkbox" name="entry[<?php echo $entry['id'] ?>]" />
            		<a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>">
                		<span class="entry-title"><?php echo $entry['title'] ?></span>
                    </a>
                    <img src="<?php echo get_cache_url($entry['thumb'], 340) ?>" />
            		<div class="pull-left">
                		<?php echo date("D m-d-y", strtotime($entry['published_at'])) ?><br/>
                        <?php echo $entry['published'] ? 'Published' : 'Queued' ?><br/>
                        <?php echo $entry['queue'] == 1 ? 'Normal' : 'Calendar' ?>
            		</div>
            		<div class="pull-right">
                		<?php foreach(explode(',', $entry['tags']) as $tag): ?>
                		<a href="?action=showall&tag=<?php echo $tag ?>"><?php echo $tag ?></a><br/>
                		<?php endforeach; ?>
            		</div>
            		<div class="clearfix"></div>
            		<?php if($entry['published']): ?><a href="<?php echo $baseurl . $entry['url_path'] ?>">View</a>&nbsp;|&nbsp;<?php endif; ?>
        			<a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>">Edit</a>&nbsp;|&nbsp;
        			<a href="?<?php echo http_build_query(array('action'=>'delete','id'=>$entry['id'])) ?>">Delete</a>
            		<hr />
            	</div>
            	<?php if(++$i % 4 == 0): ?>
            </div>
            <div class="row">
                <?php endif; ?>
            <?php endwhile; ?>
            </div> 
            <button type="submit"><span>Add Tag</span></button>
            <dt id="tags-label"><label for="tags" class="required">tags:</label></dt>
            <dd id="tags-element">
                <textarea class="tags" name="tags" id="tags" cols="40" rows="2"></textarea>
            </dd>
        </form>
        <?php
    break;
    case 'images':
    /* !rebuild images */
    $kinds = $db->query('SELECT `path`, `id` FROM `image_kind`');
    $kinds = $db->fetchAll($kinds);
    foreach($kinds as $key => $kind) {
        $kinds[$kind['path']] = $kind['id'];
        unset($kinds[$key]);
    }
    $entries = $db->query('SELECT * FROM `entry`;');
    echo '<div class="row">';
    while($entry = $entries->fetchArray()) {
        // get image files
        $imageFiles = glob($site_dir.'gallery/*/'.$entry['filename']);
        foreach($imageFiles as $key => $imageFile){
            $path = explode('/', dirname($imageFile));
            $kind = array_pop($path);
            if(in_array($kind, array_keys($kinds))) {
                $imageFiles[$kind] = basename($imageFile);
            }
            unset($imageFiles[$key]);
        }
        // get image db records
        $imageResult = $db->prepare("SELECT i.path as filename, k.path as dir, k.label, k.position FROM image i JOIN image_kind k ON i.kind = k.id  WHERE entry_id = :entry_id;");
        $imageResult->bindParam(":entry_id", $entry['id']);
        $imageResult = $imageResult->execute();
        $imageRows = $db->fetchAll($imageResult);
        foreach($imageRows as $key => $row) {
            $imageRows[$row['dir']] = $row['filename'];
            unset($imageRows[$key]);
        }
        $change = false;
        $add = array_diff_assoc($imageFiles, $imageRows);
        if(count($add) > 0) {
        	foreach($add as $kind => $filename) {
                if(isset($kinds[$kind]) && file_exists($site_dir."gallery/{$kind}/{$filename}")){                
                    $ins = $db->prepare("INSERT INTO image (entry_id, path, kind) VALUES (:entry_id, :path, :kind);");
                    $ins->bindValue(":entry_id", $entry['id']);
                    $ins->bindValue(":path", $filename);
                    $ins->bindValue(":kind", $kinds[$kind]);
                    execute($ins);
                    echo '<img src="'.get_cache_url($kind.'/'.$filename, 340).'" class="col-xs-2" alt="'.$entry['title'] .' - '.$kind.'" />';
                    $change = true;
                }
            } 
            elog('Added '.count($add).' image formats.', $entry['id']);
            echo "<br/>";
        }
        $del = array_diff_assoc($imageRows, $imageFiles);
        if(count($del) > 0) {
            echo 'Deleting from '.$entry['title'];
        	foreach($del as $kind => $filename) {
                if(isset($kinds[$kind]) && file_exists($site_dir."gallery/{$kind}/{$filename}") == false){                
                    $del = $db->prepare("DELETE FROM image WHERE `entry_id` = :entry_id AND `path` = :path AND `kind` = :kind LIMIT 1;");
                    $del->bindValue(":entry_id", $entry['id']);
                    $del->bindValue(":path", $filename);
                    $del->bindValue(":kind", $kinds[$kind]);
                    execute($del);
                    echo ' '.$kind;
                    $change = true;
                }
            } 
            echo "<br/>";
        }
        if($change) {
            $flag = $db->prepare('UPDATE `entry` SET `published` = NULL, modified_at = datetime("now") WHERE `id` = :id');
            $flag->bindValue(':id', $entry['id']);
            execute($flag);
        }
    }
    echo '</div>';
    break;
    case 'tags':
    /* !show tags */
    if(!empty($_POST['tag_id']) && isset($_POST['name'])) {
        $sql = $db->prepare('UPDATE tag SET name = :name WHERE id = :id;');
        $sql->bindParam(':id', $_POST['tag_id']);
        $sql->bindParam(':name', $_POST['name']);
        execute($sql);
        echo '<h3>Set tag ID '.$_POST['tag_id'].' to '.($_POST['name'] ? 'name' : 'not name') .'.</h3>';
    }
    if(!empty($_GET['tag_id']) && !empty($_GET['delete'])) {
        echo '<h3>Deleting Tag ID '.$_GET['tag_id'].'.</h3>';
        $sql = $db->prepare('DELETE FROM tag WHERE id = :id;');
        $sql->bindParam(':id', $_GET['tag_id']);
        execute($sql);
    }
    $tags = $db->query('SELECT t.*, count(et.tag_id) as count FROM tag t LEFT OUTER JOIN entry_tag et ON et.tag_id = t.id GROUP BY t.id ORDER by t.id DESC;');
    ?>
    <table>
        <thead>
            <th></th>
            <th>Title</th>
            <th>Slug</th>
            <th>Count</th>
            <th>Name</th>
            <th></th>
        </thead>
        <tbody>
            <?php while($tag = $tags->fetchArray()): ?>
            <tr class="<?php echo $tag['name'] ? 'name' : 'not-name' ?>">
                <form action="?<?php echo http_build_query($_GET) ?>" method="post">
                <td><?php echo $tag['id'] ?></td>
                <td><a href="?action=showall&tag=<?php echo $tag['slug'] ?>" target="_blank"><?php echo $tag['title'] ?></a></td>
                <td><a href="?action=showall&tag=<?php echo $tag['slug'] ?>" target="_blank"><?php echo $tag['slug'] ?></a></td>
                <td><?php echo $tag['count'] ?></td>
                <td><?php echo $tag['name'] ? 'Name' : 'Not Name' ?>
                    <input type="hidden" value="<?php echo $tag['id'] ?>" name="tag_id" />
                    <input type="hidden" value="<?php echo $tag['name'] ? '0' : '1' ?>" name="name" />
                </td>
                <td class="text-right">
                    <a href="?action=tags&delete=1&tag_id=<?php echo $tag['id'] ?>">Delete</a>
                    <input type="submit" value="switch" />&nbsp;
                </td>
                </form>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php
    break;
    ?>
<?php endswitch; ?>
<?php endif; ?>
<hr />
<?php /*  ! admin home begins */ ?>
<h1><a href="/">Entry Queue</a></h1>
<h3>
    <a href="/phpliteadmin.php" target="_blank">Database</a>&nbsp;|&nbsp;
    <a href="?action=showall">View All</a>&nbsp;|&nbsp;
    <a href="?action=images">Update Images</a>&nbsp;|&nbsp;
    <a href="?action=tags">Tags</a>
</h3>
<?php
$sql = 'SELECT published_at FROM entry ORDER BY published_at DESC LIMIT 1;';
$result = $db->query($sql);
$last = $result->fetchArray();
$last = new DateTime($last['published_at']);

$now = new DateTime();
$marker = new DateTime();

/** @var DateTime $marker */
$marker = $marker->sub(new DateInterval('P' . $now->format('w') . 'D'));
$increment = new DateInterval('P1D');
$month = $marker->format('m');

$repost = $db->prepare('SELECT e.*, group_concat(t.slug) AS tags FROM entry e JOIN entry_tag et ON et.entry_id = e.id JOIN tag t ON t.id = et.tag_id WHERE published IS NULL AND date(published_at) < date(:published_at) GROUP BY et.entry_id ORDER BY published_at ASC;');
$repost->bindValue(':published_at', $marker->format('Y-m-d'));
$reposts = $repost->execute();
?>
<form id="calendar" action="?<?php echo http_build_query(array('action' => "reorder")); ?>" method="post">
<div class="calendar">
	<div>Su</div>
	<div>M</div>
	<div>Tu</div>
	<div>W</div>
	<div>Th</div>
	<div>F</div>
	<div>Sa</div>
	<?php while($marker < $last): ?>
	<?php for($day = 0; $day < 7; $day++): ?>
	    <?php 
    	    $sql = $db->prepare('SELECT e.*, group_concat(t.slug) AS tags FROM entry e JOIN entry_tag et ON et.entry_id = e.id JOIN tag t ON t.id = et.tag_id WHERE date(e.published_at) = date(:published_at) GROUP BY et.entry_id');
            $sql->bindValue(':published_at', $marker->format('Y-m-d'));
            $entries = $sql->execute();
            $entry = $entries->fetchArray();
            $class = $entry['id'] ? 'item' : '';
            $class .= $entry['published'] ? ' live' : '';
        ?>
	    <div<?php echo ($class ? (' class="'.$class.'"') : '') ?>>
    	    <?php if($day == 0): ?>
    	    <?php echo $marker->format('F d'); ?><br/>
            <?php endif; ?>
            <?php if($entry['id']): ?>
                <?php if(new DateTime($entry['published_at']) > $now): ?>
            	<input type="hidden" name="entry_id[<?php echo $entry['queue'] ?>][]" value="<?php echo $entry['id'] ?>" />
            	<?php endif; ?>
                <img src="<?php echo get_cache_url($entry['thumb'], 340) ?>" />
        		<a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>">
            		<span class="entry-title"><?php echo $entry['title'] ?></span>
                </a>
        		<div class="clearfix"></div>
                <?php echo $entry['queue'] == 1 ? 'Normal' : 'Calendar' ?><br/>
        		<?php foreach(explode(',', $entry['tags']) as $tag): ?>
        		<a href="?action=showall&tag=<?php echo $tag ?>"><?php echo $tag ?></a><br/>
        		<?php endforeach; ?>
        		<div class="clearfix"></div>
        		<?php if($entry['published']): ?><a href="<?php echo $baseurl . $entry['url_path'] ?>">View</a>&nbsp;|&nbsp;<?php endif; ?>
    			<a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>">Edit</a>&nbsp;|&nbsp;
    			<a href="?<?php echo http_build_query(array('action'=>'delete','id'=>$entry['id'])) ?>">Delete</a>
            <?php else: ?>
                &nbsp;
    	    <?php endif; ?>
            <?php while($entry = $entries->fetchArray()): ?>
                <?php if(new DateTime($entry['published_at']) > $now): ?>
                    <input type="hidden" name="entry_id[<?php echo $entry['queue'] ?>][]" value="<?php echo $entry['id'] ?>" />
                <?php endif; ?>
            <?php endwhile; ?>
	    </div>
	    <?php $marker->add($increment); ?>
	<?php endfor; ?>
	<?php endwhile; ?>
</div>
<button type="submit"><span>Save</span></button>
</form>
<h4>Reposts</h4>
<div class="calendar reposts">
<?php
while($entry = $reposts->fetchArray()){
    ?>
    <div>
        <?php if($entry['id']): ?>
            <?php if(new DateTime($entry['published_at']) > $now): ?>
        	<input type="hidden" name="entry_id[<?php echo $entry['queue'] ?>][]" value="<?php echo $entry['id'] ?>" />
        	<?php endif; ?>
            <img src="<?php echo get_cache_url($entry['thumb'], 340) ?>" />
    		<a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>">
        		<span class="entry-title"><?php echo $entry['title'] ?></span>
            </a>
    		<div class="clearfix"></div>
    		<?php echo format_date($entry['published_at'], 1) ?><br/>
            <?php echo $entry['queue'] == 1 ? 'Normal' : 'Calendar' ?><br/>
    		<?php foreach(explode(',', $entry['tags']) as $tag): ?>
    		<a href="?action=showall&tag=<?php echo $tag ?>"><?php echo $tag ?></a><br/>
    		<?php endforeach; ?>
    		<div class="clearfix"></div>
			<a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>">Edit</a>&nbsp;|&nbsp;
			<a href="?<?php echo http_build_query(array('action'=>'delete','id'=>$entry['id'])) ?>">Delete</a>
        <?php else: ?>
            &nbsp;
	    <?php endif; ?>
    </div>
    <?php
}
?>
</div>


<h1>New Images</h1>
<p>Normal Queue:   <?php echo format_date($queue->getLastQueuedDate(1)) ?></p>
<p>Calendar Queue: <?php echo format_date($queue->getLastQueuedDate(2), "short") ?></p>
<?php 
    chdir($site_dir.'gallery/widescreen/');
    $files = glob('*.jpg');
    
    $imageResult = $db->query("SELECT path FROM image WHERE kind = 8;");
    $images = array();
    while($image = $imageResult->fetchArray()){
        $images[] = $image['path'];
    }
    $images = array_diff($files, $images);
?>
<div class="row">
<?php foreach ($images as $image) : ?>
	<div class="col-xs-6 col-sm-4 col-md-3">
		<p class="entry-title"><a href="?<?php echo http_build_query(array('action'=>'newprocess', 'image' => $image)) ?>"><?php echo $image ?></a></p>
		<div class="entry-image"><a href="?<?php echo http_build_query(array('action'=>'newprocess', 'image' => $image)) ?>"><img src="<?php echo get_cache_url('widescreen/' . $image, 340) ?>" alt="<?php echo codeToName(str_replace(".jpg", '', strtolower($image))) ?>"/></a></div>
	</div>
<?php endforeach ?>
</div>
<h2><br/><br/></h2>
</div>
</body>
<?php 

    function execute($statement) {
        global $db;
        if($statement->execute())
            return true;
        else {
            echo '<h2><em style="color:#de8b52;">' . $db->lastErrorMsg() . "</em></h2>";
            die();
        }
    }
    

  
  
  
  