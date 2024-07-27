<?php
include '../config.php';

// SQL to fetch distinct term_slug
$sql = "SELECT DISTINCT term_slug FROM castellanario";

$result = $db_connection->query($sql);

// Check if we have results
if ($result->num_rows > 0) {
    // Header for XML Sitemap
    header('Content-Type: application/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    // Output data of each row
    while($row = $result->fetch_assoc()) {
        $slug = htmlspecialchars($row['term_slug']); // Sanitize to avoid XML errors
        echo "<url>";
        echo "<loc>https://castellanario.com/$slug</loc>";
        echo "</url>";
    }

    echo '</urlset>';
} else {
    echo "0 results";
}