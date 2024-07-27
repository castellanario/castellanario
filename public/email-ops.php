<?php
require '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
include '../db-setup.php';
require '../functions.php';

// sleep 4 secs in case anyone wanna try a million reqs to bruteforce my awesome token
sleep(4);

// Yo bro, is stuff set? can ya pliiiz clean it up a bit?
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$tokensito = isset($_GET['tokensito']) ? $_GET['tokensito'] : null;

if (!isset($_GET['action']) || !$id || !is_numeric($id) || $tokensito !== EMAIL_OPS_SEKRET_TOKENSITO) {
    echo json_encode(['success' => false, 'message' => 'yo wtf?']);
    exit;
}

// Lezzz go!
switch($_GET['action']) {
    case 'ok-image-upload':
        $query = "UPDATE `castellanario` SET `accepted` = 1 WHERE `id` = $id";
        $msg_if_ok = 'oook, will upload the image sir';
        break;
    case 'delete-this-sht':
        $query = "DELETE FROM `castellanario` WHERE `id` = $id";
        $img = __DIR__ . '/images/' . $id . '.jpg';
        if (file_exists($img)) {
            unlink($img);
        }
        $msg_if_ok = 'i deleted this shit for you';
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'yo wtf?']);
        exit;
}

if ($db_connection->query($query)) {
    echo json_encode(['success' => true, 'message' => $msg_if_ok]);
} else {
    echo json_encode(['success' => false, 'message' => $db_connection->error]);
}