<?php 
    
$db = new PDO('sqlite:'.dirname(__FILE__).'/index.sqlite');

if(isset($_GET['q'])){
    $search = $_GET['q'];
    
    if($search == 'new') {
        $sql = "SELECT * FROM `posts` WHERE 1";
    } else {
        $search = '%' . str_replace(' ', '%', $search) . '%';
        $sql = "SELECT * FROM `posts` WHERE `name` LIKE :search";
    }
    if(!isset($_GET['b']) || $_GET['b'] == 'off')
        $sql .= ' AND `bad` = 0';
    if(!isset($_GET['r']) || $_GET['r'] == 'off')
        $sql .= ' AND `reject` = 0';
        
    $sql .= ' ORDER BY `id` DESC';
    if($search == 'new') {
        $sql .= " LIMIT 50";
        $sql = $db->prepare($sql);
    } else {
        $sql = $db->prepare($sql);
        $sql->bindParam(':search', $search);
    }
    $sql->execute();
    
    $posts = $sql->fetchAll();
        
    ?>
    <?php foreach($posts as $post): ?>
    <div class="item" id="<?php echo $post['id'] ?>" data-image="http://fetch.spartacuswallpaper.com/cache/<?php echo $post['id'] ?>" style="display:none;">
        <a class="reject">X</a>
        <a class="name" href="http://fetch.spartacuswallpaper.com/queue.php?url=<?php echo $post['url'] ?>" ><span ><?php echo $post['name'] ?></span></a>
    </div>
    <?php endforeach;
} elseif(isset($_GET['url'])) {
    $ch = curl_init('http://fetch.spartacuswallpaper.com/queue.php?url='.$_GET['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    echo curl_exec($ch);
}
