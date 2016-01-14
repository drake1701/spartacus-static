<?php
$db = new PDO('sqlite:'.dirname(__FILE__).'/index.sqlite');
$sql = $db->prepare("SELECT * FROM `posts` WHERE `cached` IS NOT NULL AND (`reject` = 1 OR `bad` = 1);");
$sql->execute();
$posts = $sql->fetchAll();
$counter = 0;
foreach($posts as $post) {
    if(file_exists(dirname(__FILE__).'/cache/'.$post['id'])) {
        unlink(dirname(__FILE__).'/cache/'.$post['id']);
        $counter++;
        $sql = $db->prepare('UPDATE `posts` SET `cached` = null WHERE `id` = :id;');
        $sql->bindValue(':id',  $post['id']);    
        $sql->execute();
    }
}
echo "\n---\nCache Cleanup: $counter files removed.\n";
?>