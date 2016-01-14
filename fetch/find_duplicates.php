<?php

$baseDir = '/Volumes/Dropbox/scratch/';

$db = new PDO('sqlite:index.sqlite');

if(!empty($argv[1])){
    $db->query('DROP TABLE names;');
    $db->query('CREATE TABLE `names` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `name` TEXT, `processed` INT DEFAULT 0)');
    $names = glob($baseDir.'*', GLOB_ONLYDIR);
    $i = 0;
    foreach($names as $name){
        if(basename($name) == "~archive") continue;
        $sql = $db->prepare('INSERT INTO `names` (`name`, `processed`) VALUES (:name, 0);');
        $sql->bindValue(':name', $name);
        $sql->execute();
        $i++;
    }
    echo "$i names loaded\n";
} else {
    while($row = $db->query('SELECT * FROM `names` WHERE processed = 0 LIMIT 1;')->fetchObject()) {
        $name = $row->name;
        
        echo basename($name)."\n";
        $nameImages = array();
        $nameImages = array_merge($nameImages, glob($name.'/*.jpg'));
        $nameImages = array_merge($nameImages, glob($name.'/*.jpeg'));
        $nameImages = array_merge($nameImages, glob($name.'/*.png'));
        
        $nameImages = array_merge($nameImages, glob($name.'/*/*.jpg'));
        $nameImages = array_merge($nameImages, glob($name.'/*/*.jpeg'));
        $nameImages = array_merge($nameImages, glob($name.'/*/*.png'));

        foreach($nameImages as $key => $image){
            if(!file_exists($image)) continue;
            
            $info = getimagesize($image);
            foreach($nameImages as $key2 => $image2){
                if($image == $image2 || !file_exists($image2)) continue;
                if(sha1_file($image) == sha1_file($image2)){
                    echo "files match $image $image2\n";
                    unlink($image2);
                    unset($nameImages[$key2]);
                    break;
                }
            }
        }
        $sql = $db->prepare('UPDATE `names` SET `processed` = 1 WHERE `id` = :id;');
        if(!$sql){
            print_r($db->errorInfo());
            die();
        }
        $sql->bindParam(':id', $row->id);
        $sql->execute();
    }
}

?>
