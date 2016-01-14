<?php 
    $names = glob('/Volumes/Dropbox/scratch/*', GLOB_ONLYDIR);
    foreach($names as $name){
        $nameImages = array();
        $posts = glob($name.'/*', GLOB_ONLYDIR);
        foreach($posts as $post){
            $postImages = array();
            $postImages = array_merge($postImages, glob($post.'/*.jpg'));
            $postImages = array_merge($postImages, glob($post.'/*.jpeg'));
            $postImages = array_merge($postImages, glob($post.'/*.png'));
            
            if(count($postImages) < 2){
                $fullPath = explode('/', $post);
                array_pop($fullPath);
                $parentPath = implode('/', $fullPath);
                foreach($postImages as $key => $image){
                    exec('mv "'.$post.'/'.basename($image).'" "'.$parentPath.'/"');
                    $postImages[$key] = $parentPath . '/' . basename($image);
                }
                echo 'Delete '.basename($post)."\n";
                exec('rm -rf "' . $post .'"');
            }
            $nameImages = array_merge($nameImages, $postImages);
        }
        if(count($nameImages) < 2){
            echo 'Delete '.basename($name)."\n";
            exec('rm -rf "'.$name.'"');
        }

        foreach($nameImages as $key => $image){
            if(!file_exists($image)) continue;
            
            $info = getimagesize($image);
            if($info['mime'] == 'image/jpeg' && count($info) > 2){
                if($info[0] < 1000 && $info[1] < 1000){
                    echo "Delete $image, too small.\n";
                    unlink($image);
                    continue;
                } else {
                    $prefix = $info[0].'x'.$info[1];
                    if(strpos(basename($image), $prefix) === false){
                        echo basename($image) . ' -> ' . $prefix . ' ' . basename($image) . "\n";
                        rename($image, dirname($image) . '/' . $prefix . ' ' . basename($image));
                    }
                }
            }
        }
    }

?>