<?php

require_once 'functions.php';

$queue = __DIR__ . '/queue/';
if(!is_dir($queue)) {
	mkdir($queue, 0775);
	chmod($queue, 0775);
}

if(!empty($_GET['url'])) {
	slog('add '.$_GET['url'] . ' to queue');
	file_put_contents($queue . trim(preg_replace("#(http|www\\.|\\.com|[:/\\.])+?#", '-', $_GET['url']), '-'), $_GET['url']);
} else {
	slog('run queue');
	$files = glob($queue . '*');
	foreach($files as $file) {
		$url = file_get_contents($file);
		exec('php ' . __DIR__ . '/fetch.php ' . $url);
		unlink($file);
	}
}
