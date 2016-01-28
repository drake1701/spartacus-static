<?php
/**
 * @author		Dennis Rogers
 * @address		www.drogers.net
 */
 require_once("../app.php");
 require_once("../queue.php");
 $queue = new Queue();
?>
<html>
<head>
    <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script type="text/javascript">
    jQuery(function() {
      jQuery( "#sortable" ).sortable();
      jQuery( "#sortable" ).disableSelection();
    });
    </script>
    <link href="<?php echo $baseurl ?>css/global.css" media="screen" rel="stylesheet" type="text/css" />
    <link href="<?php echo $baseurl ?>css/bootstrap.min.css" media="screen" rel="stylesheet" type="text/css" />
    <link href="<?php echo $baseurl ?>css/custom.css" media="screen" rel="stylesheet" type="text/css" />
</head>
<body>
<br/>
<div class="container">
<?php if(!empty($_GET['action'])): ?>
<?php switch($_GET['action']): 
          case "edit": ?>
    <?php case "newprocess" ?>
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
            "content" => null,
            "tags" => preg_replace("#-[ivxlm]*\$#", "", str_replace(".jpg", '', strtolower($image))),
            "url_path" => str_replace(".jpg", ".html", $image),
        );
    }
    ?>
    <h1>Edit</h1>
    <a href="#" class="col-xs-6 pull-right" onclick="window.open('<?php echo $baseurl ?>gallery/widescreen/<?php echo $entry['filename'] ?>', '','toolbar=no, scrollbars=yes, resizable=yes, top=10, left=10, width=960, height=600');">
	    <img src="<?php echo $baseurl ?>gallery/preview/<?php echo $entry['filename'] ?>">
    </a>
<form enctype="application/x-www-form-urlencoded" action="?action=save" method="post">
    <dl class="zend_form">
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
            <textarea name="tags" id="tags" cols="40" rows="5"><?php echo $entry['tags'] ?></textarea>
        </dd>
        <dt id="url_path-label"><label for="url_path" class="required">url path:</label></dt>
        <dd id="url_path-element">
        <input type="text" name="url_path" id="url_path" value="<?php echo $entry['url_path'] ?>">
        </dd>
        <dt id="filename-label">&nbsp;</dt>
        <dd id="filename-element">
            <input type="hidden" name="filename" value="<?php echo $entry['filename'] ?>" id="filename">
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
		$slug = trim(str_replace(' ', '-', $slug));
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
        /* !reordering */
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
    break; ?>
    <?php 
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
        echo '<h1><a href="/">All Posts</a></h1>';
        $result = $db->query("SELECT * FROM entry ORDER BY published_at DESC;");
        ?>
        <table class="text-nowrap">
            <thead>
            	<tr>
                	<td></td>
            		<td>Title</td>
            		<td>Publish Date</td>
            		<td>Published</td>
            		<td>Queue</td>
            		<td>Actions</td>
            	</tr>
            </thead>
            <tbody id="sortable">
            <?php while($entry = $result->fetchArray()): ?>
            	<tr>
                	<td><img src="<?php echo $baseurl ?>gallery/thumb/<?php echo $entry['filename'] ?>" /></td>
            		<td><a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>"><?php echo $entry['title'] ?></a></td>
            		<td><?php echo date("D m-d-Y", strtotime($entry['published_at'])) ?></td>
            		<td><?php echo $entry['published'] ?></td>
            		<td><?php echo $entry['queue'] == 1 ? 'N' : 'C' ?></td>
            		<td>
                		<?php if($entry['published']): ?><a href="<?php echo $baseurl . $entry['url_path'] ?>">View</a><br/><?php endif; ?>
            			<a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>">Edit</a><br/>
            			<a href="?<?php echo http_build_query(array('action'=>'delete','id'=>$entry['id'])) ?>">Delete</a>
            		</td>
            	</tr>
            <?php endwhile; ?>
            </tbody>
        </table>    
        <?php
    break;
    case 'images':
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
            $path = explode('/', $imageFile);
            $imageFiles[$path[6]] = basename($imageFile);
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
                    echo '<img src="'.$baseurl.'gallery/'.$kind.'/'.$filename.'" class="col-xs-2" alt="'.$entry['title'] .' - '.$kind.'" />';
                    $change = true;
                }
            } 
            echo "\n";
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
            echo "\n";
        }
        if($change) {
            $flag = $db->prepare('UPDATE `entry` SET `published` = NULL WHERE `id` = :id');
            $flag->bindValue(':id', $entry['id']);
            execute($flag);
        }
    }
    echo '</div>';
    break;
    ?>
<?php endswitch; ?>
<?php endif; ?>
<hr />
<h1><a href="/">Entry Queue</a></h1>
<h3><a href="/phpliteadmin.php" target="_blank">Database</a>&nbsp;|&nbsp;<a href="?action=showall">View All</a>&nbsp;|&nbsp;<a href="?action=images">Update Images</a></h3>
<?php
$result = $db->query("SELECT * FROM entry WHERE published IS NULL ORDER BY published_at;");
$columns = array("id", "title", "published_at");
?>
<form action="?<?php echo http_build_query(array('action' => "reorder")); ?>" method="post">
<table style="width:auto;">
	<thead>
		<tr>
			<td>ID</td>
			<td>Title</td>
			<td>Publish Date</td>
			<td>Actions</td>
		</tr>
	</thead>
	<tbody id="sortable">
    <?php while($entry = $result->fetchArray()): ?>
    	<tr>
    		<td><input type="hidden" name="entry_id[<?php echo $entry['queue'] ?>][]" value="<?php echo $entry['id'] ?>" /><?php echo $entry['id'] ?></td>
    		<td><?php echo $entry['title'] ?></td>
    		<td><?php echo date("l, M jS, Y", strtotime($entry['published_at'])) ?></td>
    		<td>
    			<a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>">Edit</a>&nbsp;|&nbsp;
    			<a href="?<?php echo http_build_query(array('action'=>'delete','id'=>$entry['id'])) ?>">Delete</a>
    		</td>
    	</tr>
    <?php endwhile; ?>
	</tbody>
</table>
<button type="submit"><span>Save</span></button>
</form>


<h1>New Images</h1>
<p>Normal Queue:   <?php echo format_date($queue->getLastQueuedDate(1)) ?></p>
<p>Calendar Queue: <?php echo format_date($queue->getLastQueuedDate(2), "short") ?></p>
<?php 
    chdir($site_dir.'gallery/thumb/');
    $files = glob('*.jpg');
    
    $imageResult = $db->query("SELECT path FROM image WHERE kind = 7;");
    $images = array();
    while($image = $imageResult->fetchArray()){
        $images[] = $image['path'];
    }
    $images = array_diff($files, $images);
?>
<div class="entry-grid row">
<?php foreach ($images as $image) : ?>
	<div class="col-xs-6 col-sm-4 col-md-3">
		<p class="entry-title"><a href="?<?php echo http_build_query(array('action'=>'newprocess', 'image' => $image)) ?>"><?php echo $image ?></a></p>
		<div class="entry-image"><a href="?<?php echo http_build_query(array('action'=>'newprocess', 'image' => $image)) ?>"><img src="<?php echo $baseurl ?>gallery/thumb/<?php echo $image ?>" alt="<?php echo codeToName(str_replace(".jpg", '', strtolower($image))) ?>"/></a></div>
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
    
?>