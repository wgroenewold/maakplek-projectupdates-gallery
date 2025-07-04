<?php

// Configuration
$server_url = "https://samen.maakplek.nl";
$username = "wouter";
$password = "Z3ST13NT3K3NSW4CHTW00RDWTF?!";
$channel_id = "xmkszxdmmfdp9f33wa9joza1xo";
$cache_dir = __DIR__ . '/cache';

// Trigger cleanup once per day
$cleanup_interval = 86400; // 24 hours
$last_cleanup_file = "$cache_dir/.last_cleanup";

if (!file_exists($last_cleanup_file) || time() - filemtime($last_cleanup_file) > $cleanup_interval) {
    include __DIR__ . '/cleanup.php';
    touch($last_cleanup_file);
}

// Authenticate and get token
function getToken($server_url, $username, $password) {
    $ch = curl_init("$server_url/api/v4/users/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'login_id' => $username,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    curl_close($ch);
    preg_match('/^Token:\s*(.*)$/mi', $header, $matches);
    return $matches[1] ?? null;
}

// Helper to fetch JSON from the Mattermost API
function apiGetJson($url, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// Get token
$token = getToken($server_url, $username, $password);
if (!$token) {
    die("Login to Mattermost failed.");
}

// Fetch posts from the channel
$posts = apiGetJson("$server_url/api/v4/channels/$channel_id/posts", $token);
$files = [];

// Collect all file IDs from posts
foreach ($posts['posts'] ?? [] as $post) {
    if (!empty($post['file_ids'])) {
        foreach ($post['file_ids'] as $file_id) {
            $files[] = $file_id;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gallery</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lightgallery-bundle.min.css" />
    <style>
        body { font-family: sans-serif; background: #f5f5f5; margin: 2em; }
        #gallery a { margin: 5px; display: inline-block; }
        #gallery img { width: 150px; height: auto; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
<h1>Maakplek #projectupdates fotogallerij</h1>
<div id="gallery">
<?php foreach ($files as $file_id): ?>
    <a href="proxy.php?file_id=<?= htmlspecialchars($file_id) ?>">
        <img src="proxy.php?file_id=<?= htmlspecialchars($file_id) ?>" alt="">
    </a>
<?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/lightgallery.umd.min.js"></script>
<script>
    lightGallery(document.getElementById('gallery'), {
        thumbnail: true,
        zoom: true,
        download: false
    });
</script>
</body>
</html>
