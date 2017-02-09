<?php
/**
 * @author     Spartacus <spartacuswallpaper@gmail.com>
 * @address    www.spartacuswallpaper.com
 */

require_once(dirname(__FILE__).'/app.php');
echo "--- Processing Image File Changes ---\n";

$kinds = $db->query('SELECT `path`, `id` FROM `image_kind`');
$kinds = $db->fetchAll($kinds);
foreach($kinds as $key => $kind) {
    $kinds[$kind['path']] = $kind['id'];
    unset($kinds[$key]);
}

$entries = $db->query('SELECT * FROM `entry`;');

while($entry = $entries->fetchArray()) {
    $change = false;
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
    $imageResult = $db->prepare("SELECT i.path as filename, k.path as dir, k.label, k.position FROM image i JOIN image_kind k ON i.kind = k.id  WHERE entry_id = :entry_id ORDER BY k.position ASC;");
    $imageResult->bindParam(":entry_id", $entry['id']);
    $imageResult = $imageResult->execute();
    $imageRows = $db->fetchAll($imageResult);
    $first = false;
    foreach($imageRows as $key => $row) {
        if($first == false && $entry['thumb'] != $row['dir'] . '/' . $row['filename']) {
            $thumb = $db->prepare("UPDATE entry SET thumb = :thumb WHERE id = :id;");
            $thumb->bindValue(":id", $entry['id']);
            $thumb->bindValue(":thumb", $row['dir'] . '/' . $row['filename']);
            $result = $thumb->execute();
            $change = true;
            echo 'Set thumb for '.$entry['id'].' to '.$row['dir'] . '/' . $row['filename']."\n";
        }
        $first = true;
        $imageRows[$row['dir']] = $row['filename'];
        unset($imageRows[$key]);
    }
    $add = array_diff_assoc($imageFiles, $imageRows);
    if(count($add) > 0) {
        echo 'Adding to '.$entry['title']."\n";
    	foreach($add as $kind => $filename) {
            if(isset($kinds[$kind]) && file_exists($site_dir."gallery/{$kind}/{$filename}")){                
                $ins = $db->prepare("INSERT INTO image (entry_id, path, kind) VALUES (:entry_id, :path, :kind);");
                $ins->bindValue(":entry_id", $entry['id']);
                $ins->bindValue(":path", $filename);
                $ins->bindValue(":kind", $kinds[$kind]);
                $ins->execute();
                echo $kind.'/'.$filename."\n";
                $change = true;
            }
        }
        elog('Added '.count($add).' image formats.', $entry['id']);
    }
    $del = array_diff_assoc($imageRows, $imageFiles);
    if(count($del) > 0) {
        echo 'Deleting from '.$entry['title']."\n";
    	foreach($del as $kind => $filename) {
            if(isset($kinds[$kind]) && file_exists($site_dir."gallery/{$kind}/{$filename}") == false){                
                $del = $db->prepare("DELETE FROM image WHERE `entry_id` = :entry_id AND `path` = :path AND `kind` = :kind LIMIT 1;");
                $del->bindValue(":entry_id", $entry['id']);
                $del->bindValue(":path", $filename);
                $del->bindValue(":kind", $kinds[$kind]);
                $del->execute();
                echo $kind."\n";
                $change = true;
            }
        }
    }
    if($change) {
        $flag = $db->prepare('UPDATE `entry` SET `published` = NULL, modified_at = datetime("now") WHERE `id` = :id');
        $flag->bindValue(':id', $entry['id']);
        $flag->execute();
        $cachefile = transcode(str_replace('.jpg', '', $entry['filename'])).'.jpg';
        $cache = glob($site_dir.'gallery/cache/*/*/'.$cachefile);
        foreach($cache as $file) {
            unlink($file);
        }
        echo "Cleared ".count($cache)." cache files.\n";
        echo "---\n";
    }
}
echo "--- Done ---\n";

