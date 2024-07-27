<?php
// A composerless dotenv approach which goes against all best practices (see my other github.com/mapamy project for good practices)
include '../config.php';

// Yo bro, is stuff set? can ya pliiiz clean it up a bit?
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$tokensito = isset($_GET['tokensito']) ? $_GET['tokensito'] : null;

if (!$id || !is_numeric($id) || $tokensito !== UPLOAD_REVIEW_TOKEN) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Lezzz go!
$query = "UPDATE `castellanario` SET `accepted` = 1 WHERE `id` = $id";

if ($db_connection->query($query)) {
    echo json_encode(['success' => true, 'message' => 'Upload approved']);
} else {
    echo json_encode(['success' => false, 'message' => $db_connection->error]);
}