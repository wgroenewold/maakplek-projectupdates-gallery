<?php
// Path to cache folder
$cache_dir = __DIR__ . '/cache';
// Files older than this will be removed (30 days)
$expire_time = time() - (60 * 60 * 24 * 30);

foreach (glob("$cache_dir/*") as $file) {
    if (is_file($file) && filemtime($file) < $expire_time) {
        unlink($file);
    }
}
