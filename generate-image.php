<?php
include __DIR__ . '/config.php';

// Select word by id ascending where imaged = 0
$query = "SELECT * FROM `castellanario` WHERE `imaged` = 0 ORDER BY `id` ASC LIMIT 1";
$result = $db_connection->query($query);

if ($result->num_rows > 0) {
    $word = $result->fetch_assoc();
    $id = $word['id'];
    $term = $word['term'];
    $explanation = $word['explanation'];
    $region = $word['region'];

    // Generate the image
    generateWord($id, $term, $explanation, $region);

    // Update the word to set imaged = 1
    $query = "UPDATE `castellanario` SET `imaged` = 1 WHERE `id` = $id";
    $db_connection->query($query);
}

function generateWord($filename, $term, $explanation, $region) {
    // Define image dimensions
    $imageWidth = 1000;
    $imageHeight = 1000;
    $padding = 100;

    // Create the image
    $image = imagecreatetruecolor($imageWidth, $imageHeight);

    // Define colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);

    // Fill the background with white color
    imagefilledrectangle($image, 0, 0, $imageWidth, $imageHeight, $white);

    // Define font paths
    $fontPathterm = __DIR__ . '/public/assets/SpecialElite-Regular.ttf';
    $fontPathexplanation = __DIR__ . '/public/assets/Dosis-Regular.ttf';

    // Initial font sizes
    $termFontSize = 100;
    $explanationFontSize = 40;
    $regionFontSize = 25;

    // Line heights
    $termLineHeight = 1.2;
    $explanationLineHeight = 1.5;

    // Define positions
    $termX = $padding;
    $termY = $padding + $termFontSize;
    $explanationX = $padding;
    $explanationY = $padding + 300;

    // Max dimensions for term and explanation
    $maxtermWidth = $imageWidth - 2 * $padding;
    $maxtermHeight = 200;
    $maxexplanationWidth = $imageWidth - 2 * $padding;
    $maxexplanationHeight = 400;

    // Function to wrap text and calculate bounding box
    function wrapText($fontSize, $fontPath, $text, $maxWidth) {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $testLine);
            $lineWidth = $bbox[2] - $bbox[0];
            if ($lineWidth > $maxWidth && $currentLine) {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        $lines[] = $currentLine;
        return $lines;
    }

    // Adjust font size for term
    while ($termFontSize > 10) {
        $termLines = wrapText($termFontSize, $fontPathterm, $term, $maxtermWidth);
        $totaltermHeight = count($termLines) * $termFontSize * $termLineHeight;
        if ($totaltermHeight <= $maxtermHeight) {
            break;
        }
        $termFontSize -= 2;
    }

    // Draw the term text
    foreach ($termLines as $index => $line) {
        imagettftext($image, $termFontSize, 0, $termX, $termY + $index * $termFontSize * $termLineHeight, $black, $fontPathterm, $line);
    }

    // Adjust font size for explanation
    while ($explanationFontSize > 10) {
        $explanationLines = wrapText($explanationFontSize, $fontPathexplanation, $explanation, $maxexplanationWidth);
        $totalexplanationHeight = count($explanationLines) * $explanationFontSize * $explanationLineHeight;
        if ($totalexplanationHeight <= $maxexplanationHeight) {
            break;
        }
        $explanationFontSize -= 2;
    }

    // Draw the explanation text
    foreach ($explanationLines as $index => $line) {
        imagettftext($image, $explanationFontSize, 0, $explanationX, $explanationY + $index * $explanationFontSize * $explanationLineHeight, $black, $fontPathexplanation, $line);
    }

    // Add the region label with border radius
    $regionPaddingX = 25;
    $regionPaddingY = 15;
    $regionBox = imagettfbbox($regionFontSize, 0, $fontPathexplanation, $region);
    $regionTextWidth = $regionBox[2] - $regionBox[0];
    $regionTextHeight = $regionBox[1] - $regionBox[7];
    $regionRectWidth = $regionTextWidth + $regionPaddingX * 2;
    $regionRectHeight = $regionTextHeight + $regionPaddingY * 2;
    $regionRectX = $imageWidth - $padding - $regionRectWidth;
    $regionRectY = $imageHeight - $padding - $regionRectHeight;

    // Draw rounded rectangle
    $radius = 5;
    imagefilledrectangle($image, $regionRectX + $radius, $regionRectY, $regionRectX + $regionRectWidth - $radius, $regionRectY + $regionRectHeight, $black);
    imagefilledrectangle($image, $regionRectX, $regionRectY + $radius, $regionRectX + $regionRectWidth, $regionRectY + $regionRectHeight - $radius, $black);
    imagefilledellipse($image, $regionRectX + $radius, $regionRectY + $radius, $radius * 2, $radius * 2, $black);
    imagefilledellipse($image, $regionRectX + $regionRectWidth - $radius, $regionRectY + $radius, $radius * 2, $radius * 2, $black);
    imagefilledellipse($image, $regionRectX + $radius, $regionRectY + $regionRectHeight - $radius, $radius * 2, $radius * 2, $black);
    imagefilledellipse($image, $regionRectX + $regionRectWidth - $radius, $regionRectY + $regionRectHeight - $radius, $radius * 2, $radius * 2, $black);

    // Add the region text
    imagettftext($image, $regionFontSize, 0, $regionRectX + $regionPaddingX, $regionRectY + $regionRectHeight - $regionPaddingY, $white, $fontPathexplanation, $region);

    // Save the image as a JPG file with maximum quality
    $outputFile = __DIR__ . '/public/images/' . $filename . '.jpg';
    imagejpeg($image, $outputFile, 100);

    // Destroy the image to free up memory
    imagedestroy($image);
}

