<?php
if(!empty($_GET['files'])) {
	echo showDir(__DIR__ . '/get/*') . '<br/>';
	echo showDir(__DIR__ . '/get/*/*') . '<br/>';
	echo showDir(__DIR__ . '/get/*/*/*');
} elseif(!empty($_GET['queue'])) {
	echo showDir(__DIR__ . '/queue/*') . '<br/>';
} elseif(!empty($_GET['log'])) {
	exec('tail -n 99 ' . __DIR__ . '/logs/system.log', $log);
	$log = array_reverse($log);
	echo implode("\n", $log);
} elseif(!empty($_GET['elog'])) {
    exec('tail -n 99 ' . __DIR__ . '/logs/error.log', $log);
    $log = array_reverse($log);
    echo implode("\n", $log);
} else { ?>
	<head>
		<title>fetch watcher</title>
		<style type="text/css">
			div {
				outline:1px solid black;
				float:left;
				height:45%;
				overflow:scroll;
			}
		</style>
		<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
		<script type="text/javascript">
            jQuery('document').ready(function(){
                setInterval(function(){
                    jQuery('#files').load('http://fetch.spartacuswallpaper.com/get.php?files=1');
                    jQuery('#queue').load('http://fetch.spartacuswallpaper.com/get.php?queue=1');
                    jQuery('#log').load('http://fetch.spartacuswallpaper.com/get.php?log=1');
                    jQuery('#elog').load('http://fetch.spartacuswallpaper.com/get.php?elog=1');
                }, 1000);

                jQuery('#runqueue').on('click', function(){
                    jQuery.get('http://fetch.spartacuswallpaper.com/queue.php');
                });
            });
		</script>
	</head>
	<div id="files" style="width:50%;"></div>
	<div id="queue" style="width:50%;"></div>
	<div style="width:50%;"><textarea id="log" style="width:100%;height: 100%;"></textarea></div>
    <div style="width:50%;"><textarea id="elog" style="width:100%;height: 100%;"></textarea></div>
    <a href="#" id="runqueue">Queue</a>
<?php }

function showDir($pattern) {
	$files = glob($pattern);
	foreach($files as $i => $file) {
		$files[$i] = str_replace(__DIR__, '', $file);
	}
	return(implode('<br/>', $files));
}
