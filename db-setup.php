<?php
$db_connection = new mysqli($_ENV['DB_HOSTNAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
if ($db_connection->connect_error) {
    die('Connection failed: ' . $db_connection->connect_error);
}
$db_connection->select_db($_ENV['DB_NAME']);
$db_connection->set_charset('utf8mb4');