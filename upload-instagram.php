<?php
include __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
include __DIR__ . '/db-setup.php';
include __DIR__ . '/functions.php';
include __DIR__ . '/instatoken.php';

// Get next queued and accepted word where uploaded = 0
$sql = "SELECT * FROM castellanario WHERE queued = 1 AND accepted = 1 AND uploaded = 0 ORDER BY id ASC LIMIT 1";
$result = $db_connection->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $id = $row['id'];
    $term = $row['term'];
    $term_slug = str_replace('-', '', $row['term_slug']);
    $explanation = $row['explanation'];
    $region = $row['region'];
    $region_slug = str_replace('-', '', $row['region_slug']);
    $example = $row['example'];

    $imagePath = 'https://castellanario.com/images/' . $id . '.jpg';
    $caption = "Ejemplo de uso:\n" . $example . "\n\n#" . $term_slug . " #" . $region_slug . " \n\n---------------\nVisita la web del Castellanario, añade tus expresiones favoritas del castellano y vota las que más te gusten para que las publiquemos aquí!";

    // Upload to Instagram
    $response = uploadToInstagram($imagePath, $caption, INSTAGRAM_ACCESS_TOKEN, $_ENV['INSTAGRAM_USER_ID']);

    if (isset($response['id'])) {
        // Update uploaded = 1
        $db_connection->query("UPDATE castellanario SET uploaded = 1 WHERE id = $id");
    }
}

function uploadToInstagram($imagePath, $caption, $accessToken, $userId)
{
    // Step 1: Upload the image
    $url = "https://graph.facebook.com/v20.0/$userId/media";
    $imageData = [
        'image_url' => $imagePath,
        'caption' => $caption,
        'access_token' => $accessToken,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($imageData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);
    echo $response;

    if (!isset($responseData['id'])) {
        echo 'NO CREATION ID YET';
        exit;
    }

    $creationId = $responseData['id'];


    // Step 2: Publish the image
    $publishUrl = "https://graph.facebook.com/v20.0/$userId/media_publish";
    $publishData = [
        'creation_id' => $creationId,
        'access_token' => $accessToken,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $publishUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($publishData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}