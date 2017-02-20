<?php

require_once 'functions.php';

$queue = __DIR__ . '/queue/';
if(!is_dir($queue)) mkdir($queue, 0777);

if(!empty($_GET['url'])) {
	slog('add '.$_GET['url'] . ' to queue');
	file_put_contents($queue . time(), $_GET['url']);
} else {
	slog('run queue');
	$files = glob($queue . '*');
	foreach($files as $file) {
		$url = file_get_contents($file);
		exec('php ' . __DIR__ . '/fetch.php ' . $url);
		unlink($file);
	}
	foreach(glob(__DIR__ . '/get/*', GLOB_ONLYDIR) as $name) {
		exec('tar -czf "get/' . basename($name) . '.tar.gz" -C get "' . basename($name) . '"');
		exec('rm -rf "' . $name . '"');
	}
}
