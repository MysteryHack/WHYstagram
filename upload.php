<?php
// Configuration
$uploadDir = __DIR__ . '/uploads/';
$metadataFile = $uploadDir . 'metadata.json';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Validate fields
if (!isset($_FILES['photo'], $_POST['caption'], $_POST['crappy_cam'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

$file = $_FILES['photo'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'File upload error']);
    exit;
}

// Validate file type (only JPEG)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mime !== 'image/jpeg') {
    http_response_code(400);
    echo json_encode(['error' => 'Only JPEG files allowed']);
    exit;
}

// Generate SHA1 hash of file
$hash = sha1_file($file['tmp_name']);
$filename = $hash . '.jpg';
$destination = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// Load existing metadata
$metadata = [];
if (file_exists($metadataFile)) {
    $json = file_get_contents($metadataFile);
    $metadata = json_decode($json, true) ?: [];
}

// Add new entry with timestamp
$newEntry = [
    'caption' => strip_tags($_POST['caption']),
    'file' => $filename,
    'crappy_cam' => filter_var($_POST['crappy_cam'], FILTER_VALIDATE_BOOLEAN),
    'timestamp' => time()
];

$metadata[] = $newEntry;

// Save updated metadata
if (file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update metadata']);
    exit;
}

echo json_encode(['success' => true, 'file' => $filename, 'timestamp' => $newEntry['timestamp'], 'crappy_cam' => $newEntry['crappy_cam']]);
