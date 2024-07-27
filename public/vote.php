<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $action = $_POST['action'];

    if (!$id || !is_numeric($id)) {
        echo json_encode(['success' => false, 'message' => 'wtf?']);
        exit;
    }

    if (!isset($_SESSION['votes'])) {
        $_SESSION['votes'] = [];
    }

    if (in_array($id, $_SESSION['votes'])) {
        echo json_encode(['success' => false, 'message' => 'Ya has votado']);
        exit;
    }

    if ($action === 'upvote') {
        $query = "UPDATE `castellanario` SET `upvotes` = `upvotes` + 1 WHERE `id` = $id";
    } elseif ($action === 'downvote') {
        $query = "UPDATE `castellanario` SET `downvotes` = `downvotes` + 1 WHERE `id` = $id";
    } else {
        echo json_encode(['success' => false, 'message' => 'wtf?']);
        exit;
    }

    if ($db_connection->query($query)) {
        $_SESSION['votes'][] = $id;
        $result = $db_connection->query("SELECT `upvotes`, `downvotes` FROM `castellanario` WHERE `id` = $id");
        $votes = $result->fetch_assoc();

        // If upvotes are greater than 10, set queued = 1
        if ($votes['upvotes'] > 10) {
            $db_connection->query("UPDATE `castellanario` SET `queued` = 1 WHERE `id` = $id");

            // Send myself an email to review the word before uploading
            $headers = "From: " . SERVER_FROM_EMAIL . "\r\n";
            $headers .= "Reply-To: " . SERVER_FROM_EMAIL . "\r\n";
            $headers .= "Return-Path: " . SERVER_FROM_EMAIL . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Priority: 1\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $message = '<p>La palabra con ID ' . $id . ' ha superado los 10 votos positivos. Revisala antes de subirla a la web.</p><p><img alt="imagen" src="https://castellanario.com/images/' . $id . '.jpg"></p><p><a href="https://castellanario.com/aprobar-subida.php?id=' . $id . '&tokensito=' . UPLOAD_REVIEW_TOKEN . '"</p>';

            mail(ADMIN_EMAIL, 'Nueva palabra para revisar', $message, $headers);
        }

        echo json_encode(['success' => true, 'upvotes' => $votes['upvotes'], 'downvotes' => $votes['downvotes']]);
    } else {
        echo json_encode(['success' => false, 'message' => $db_connection->error]);
    }
}
?>