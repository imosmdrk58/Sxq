<?php
/**
 * CA Certificate Downloader
 * This script downloads the latest CA certificate bundle from curl.se
 * to help resolve SSL certificate verification issues
 */

// Set time limit for long downloads
set_time_limit(300);

// Force plain text output for cleaner debugging
header('Content-Type: text/plain');

echo "===== CA Certificate Downloader =====\n";
echo "This script will download the latest CA certificate bundle to help resolve SSL verification issues\n\n";

// Define source and destination
$sourceUrl = 'https://curl.se/ca/cacert.pem';
$destPath = __DIR__ . '/cacert.pem';

echo "Source: $sourceUrl\n";
echo "Destination: $destPath\n\n";

// Check if we already have the file
if (file_exists($destPath)) {
    echo "CA certificate file already exists.\n";
    echo "Size: " . filesize($destPath) . " bytes\n";
    echo "Created: " . date('Y-m-d H:i:s', filemtime($destPath)) . "\n\n";
    echo "Would you like to re-download it? (Remove the existing file manually if needed)\n";
    echo "To use this CA bundle in your code, add the following lines:\n\n";
    echo "curl_setopt(\$ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');\n\n";
    exit;
}

// Try multiple methods to download the file

// Method 1: file_get_contents
echo "Attempting to download using file_get_contents()...\n";
if (ini_get('allow_url_fopen')) {
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);
    
    try {
        $data = @file_get_contents($sourceUrl, false, $context);
        if ($data !== false) {
            if (file_put_contents($destPath, $data)) {
                echo "SUCCESS! Downloaded " . strlen($data) . " bytes using file_get_contents()\n";
                echo "CA certificate bundle saved to: $destPath\n";
                echo "To use this CA bundle in your code, add the following line:\n\n";
                echo "curl_setopt(\$ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');\n";
                exit;
            } else {
                echo "Downloaded file but could not save it. Check write permissions.\n";
            }
        } else {
            echo "Failed to download using file_get_contents()\n";
            echo "Error: " . error_get_last()['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "file_get_contents() not available (allow_url_fopen is disabled)\n";
}

echo "\n";

// Method 2: cURL
echo "Attempting to download using cURL...\n";
if (function_exists('curl_init')) {
    try {
        $ch = curl_init($sourceUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $data = curl_exec($ch);
        
        if ($data !== false) {
            if (file_put_contents($destPath, $data)) {
                echo "SUCCESS! Downloaded " . strlen($data) . " bytes using cURL\n";
                echo "CA certificate bundle saved to: $destPath\n";
                echo "To use this CA bundle in your code, add the following line:\n\n";
                echo "curl_setopt(\$ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');\n";
                exit;
            } else {
                echo "Downloaded file but could not save it. Check write permissions.\n";
            }
        } else {
            echo "Failed to download using cURL\n";
            echo "Error: " . curl_error($ch) . "\n";
        }
        
        curl_close($ch);
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "cURL is not available\n";
}

echo "\n";

// If we get here, all methods failed
echo "All download methods failed.\n";
echo "Please manually download the CA certificate bundle from:\n";
echo "https://curl.se/ca/cacert.pem\n\n";
echo "Save it as cacert.pem in your project directory and use:\n";
echo "curl_setopt(\$ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');\n";
?>
