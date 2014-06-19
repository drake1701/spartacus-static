<?php
/**
 * @author		Dennis Rogers
 * @address		www.drogers.net
 */
 require_once("app.php");
 require_once("queue.php");
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
    <link href="<?php echo $assets_dir ?>css/global.css" media="screen" rel="stylesheet" type="text/css" />
</head>
<body style="width:80%; margin:0 auto;">
<?php if($_GET['action']): ?>
<?php switch($_GET['action']): 
          case "edit": ?>
    <?php case "newprocess" ?>
    <?php /* !new and edit form */ ?>
    <?php 
    if($_GET['id']){
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
    <a href="#" onclick="window.open('<?php echo $site_dir ?>gallery/widescreen/<?php echo $entry['filename'] ?>', '','toolbar=no, scrollbars=yes, resizable=yes, top=10, left=10, width=960, height=600');">
	    <img style="float:right;" src="<?php echo $site_dir ?>gallery/preview/<?php echo $entry['filename'] ?>">
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
        foreach($data as $key => $value){
            $query .= "$key = :$key, ";
        }
        $query = trim($query, ", ");
        $query .= " WHERE id = :id;";
        $update = $db->prepare($query);
        foreach($data as $key => $value){
            $update->bindValue(":$key", $value);
        }
        $update->execute();
        ?>
        <h2><em>Entry saved successfully.</em></h2>
        <?php
    } else {
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
        $insert->execute();
        ?>
        <h2><em>Entry created successfully.</em></h2>
        <?php        
        $data['id'] = $db->lastInsertRowID();
    }
    // !handle tags
	// Current & New
	foreach($slugs as $slug){
		$slug = trim($slug);
		if($slug == '') continue;
		$tag = $db->prepare("SELECT * FROM tag WHERE slug = :slug;");
		$tag->bindParam(":slug", $slug);
		$tag = $tag->execute();
		$tag = $tag->fetchArray();
		if($tag['id'] == false){
		    $ins = $db->prepare("INSERT INTO tag (title, slug) VALUES (:title, :slug);");
		    $ins->bindValue(":title", codeToName($slug));
		    $ins->bindValue(":slug", $slug);
		    $ins->execute();
		    $tag['id'] = $db->lastInsertRowID();
		}
		
		$linkResult = $db->prepare("SELECT * FROM entry_tag e WHERE entry_id = :entry_id AND tag_id = :tag_id;");
		$linkResult->bindParam(":entry_id", $data['id']);
		$linkResult->bindParam(":tag_id", $tag['id']);
		$linkResult = $linkResult->execute();
		$link = $linkResult->fetchArray();

		if($link['id'] == false) {
		    $ins = $db->prepare("INSERT INTO entry_tag (entry_id, tag_id) VALUES (:entry_id, :tag_id);");
		    $ins->bindValue(":tag_id", $tag['id']);
		    $ins->bindValue(":entry_id", $data['id']);
		    $ins->execute();		
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
		    $del->execute();
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
            $ins->execute();
        }
    }
    break; ?>
<?php endswitch; ?>
<?php endif; ?>
<h1>Entry Queue</h1>
<?php
$result = $db->query("SELECT * FROM entry WHERE published IS NULL;");
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
    			<a href="?<?php echo http_build_query(array('action'=>'edit','id'=>$entry['id'])) ?>">Edit</a>
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
<div class="entry-grid">
<?php foreach ($images as $image) : ?>
	<div class="entry-item">
		<p class="entry-title"><a href="?<?php echo http_build_query(array('action'=>'newprocess', 'image' => $image)) ?>"><?php echo $image ?></a></p>
		<div class="entry-image"><a href="?<?php echo http_build_query(array('action'=>'newprocess', 'image' => $image)) ?>"><img src="<?php echo $site_dir ?>gallery/thumb/<?php echo $image ?>" alt="<?php echo codeToName(str_replace(".jpg", '', strtolower($image))) ?>"/></a></div>
	</div>
<?php endforeach ?>
</div>
</body>