<?php

// Configuration
$server_url = "https://samen.maakplek.nl";
$username = "wouter";
$password = "Z3ST13NT3K3NSW4CHTW00RDWTF?!";
$cache_dir = __DIR__ . '/cache';

// Get file ID from query string
$file_id = $_GET['file_id'] ?? null;
if (!$file_id) {
    http_response_code(400);
    exit("Missing file_id");
}

$cache_path = "$cache_dir/$file_id";
$meta_path = "$cache_dir/$file_id.json";

// Login and get token
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

// Fetch JSON data from Mattermost API
function apiGetJson($url, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Get auth token
$token = getToken($server_url, $username, $password);
if (!$token) {
    http_response_code(500);
    exit("Authentication failed.");
}

// Get file info to check update time
$file_info = apiGetJson("$server_url/api/v4/files/$file_id/info", $token);
if (!$file_info || empty($file_info['update_at'])) {
    http_response_code(404);
    exit("File not found.");
}
$remote_update_time = intval($file_info['update_at'] / 1000);

// Check if cached version is valid
$use_cache = false;
if (file_exists($cache_path) && file_exists($meta_path)) {
    $meta = json_decode(file_get_contents($meta_path), true);
    if (!empty($meta['update_at']) && $meta['update_at'] >= $remote_update_time) {
        $use_cache = true;
    }
}

// Serve from cache
if ($use_cache) {
    $mime = $meta['mime_type'] ?? mime_content_type($cache_path);
    header("Content-Type: $mime");
    readfile($cache_path);
    exit;
}

// Download the file from Mattermost
$ch = curl_init("$server_url/api/v4/files/$file_id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

// Extract MIME type from headers
if (preg_match('/Content-Type:\s*(.*)/i', $header, $matches)) {
    $mime = trim($matches[1]);
} else {
    $mime = "image/jpeg"; // Default fallback
}
header("Content-Type: $mime");

// Save to cache
file_put_contents($cache_path, $body);
file_put_contents($meta_path, json_encode([
    'update_at' => $remote_update_time,
    'mime_type' => $mime
]));

// Output the image
echo $body;
