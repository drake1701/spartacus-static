<?php
foreach(glob(__DIR__ . '/get/*', GLOB_ONLYDIR) as $name) {
	exec('tar -czf "'.__DIR__.'/get/' . basename($name) . '.tar.gz" -C "'.__DIR__.'/get" "' . basename($name) . '"');
	exec('rm -rf "' . $name . '"');
}
