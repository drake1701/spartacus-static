<?php
$index = file_get_contents("saved.json.back");
//file_put_contents("saved.json.back", $index);
$index = json_decode($index);
$newIndex = array();
foreach($index as $k => $post){
    if($post->status != 1){
        $post->status = 0;
        echo $post->title."\n";
        $newIndex[] = $post;
    }
}
$json = json_encode($newIndex);
$json = str_replace("{", "\n{", $json);
file_put_contents("saved.json", $json);
print_r($errors);
?>
