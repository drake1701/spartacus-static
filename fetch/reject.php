<?php

$db = new PDO('sqlite:'.dirname(__FILE__).'/index.sqlite');

if(isset($_GET['id'])){
    $id = $_GET['id'];

    $sql = "UPDATE `posts` SET `reject` = 1 WHERE `id` = :id;";
    $sql = $db->prepare($sql);
    $sql->bindParam(':id', $id);
    $sql->execute();
}    