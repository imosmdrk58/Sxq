<?php
// filepath: c:\Users\ziggy\source\repos\Pillendoosje\Webdevelepment-MangaSite\www\debug.php
/**
 * Debug page for website troubleshooting
 * IMPORTANT: Verwijder deze pagina zodra alles werkt!
 */

// Zet error reporting aan voor debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Voeg HTML header toe
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manga Tracker - Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        .success { color: green; padding: 10px; background-color: #e6ffe6; border-radius: 4px; }
        .error { color: red; padding: 10px; background-color: #ffe6e6; border-radius: 4px; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manga Tracker - Debug Informatie</h1>
        <p><strong>Let op: Verwijder deze pagina nadat het probleem is opgelost!</strong></p>

        <!-- PHP informatie -->
        <h2>PHP Omgeving</h2>
        <ul>
            <li>PHP Versie: <?php echo phpversion(); ?></li>
            <li>Max upload: <?php echo ini_get('upload_max_filesize'); ?></li>
            <li>Max POST: <?php echo ini_get('post_max_size'); ?></li>
            <li>Memory Limit: <?php echo ini_get('memory_limit'); ?></li>
            <li>cURL beschikbaar: <?php echo function_exists('curl_init') ? 'Ja' : 'Nee'; ?></li>
            <li>allow_url_fopen: <?php echo ini_get('allow_url_fopen') ? 'Aan' : 'Uit'; ?></li>
        </ul>
        
        <!-- Database verbinding testen -->
        <h2>Database Verbinding</h2>
        <?php
        try {
            require_once('config/config.php');
            
            // Toon gecensureerde database configuratie
            echo "<pre>";
            echo "Host: " . DB_HOST . "\n";
            echo "Database: " . DB_NAME . "\n";
            echo "Gebruikersnaam: " . DB_USER . "\n";
            echo "Wachtwoord: " . (DB_PASS ? '******' : '<em>niet ingesteld</em>') . "\n";
            echo "</pre>";
            
            // Test de verbinding (PDO zou al geïnitialiseerd moeten zijn in config.php)
            global $pdo;
            
            if ($pdo) {
                // Test een eenvoudige query
                $stmt = $pdo->query("SELECT 1");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    echo "<div class='success'>Database verbinding succesvol! ✅</div>";
                    
                    // Controleer of tabellen bestaan
                    $tables = ['users', 'bookmarks', 'comments', 'reads_log'];
                    echo "<h3>Tabellen Controle</h3>";
                    echo "<ul>";
                    
                    foreach ($tables as $table) {
                        try {
                            $stmt = $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
                            echo "<li>{$table}: <span class='success'>OK</span></li>";
                        } catch (Exception $e) {
                            echo "<li>{$table}: <span class='error'>Niet gevonden - " . $e->getMessage() . "</span></li>";
                        }
                    }
                    
                    echo "</ul>";
                } else {
                    echo "<div class='error'>Database verbinding mislukt bij het uitvoeren van de query.</div>";
                }
            } else {
                echo "<div class='error'>Database verbinding niet geïnitialiseerd in config.php</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Database fout: " . $e->getMessage() . "</div>";
        }
        ?>

        <!-- API bereikbaarheid testen -->
        <h2>API Bereikbaarheid</h2>
        <?php
        try {
            // Test de Jikan API
            $apiUrl = "https://api.jikan.moe/v4/manga?q=naruto&limit=1";
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);
            
            echo "API URL: " . $apiUrl . "<br>";
            
            // Probeer met file_get_contents
            if (ini_get('allow_url_fopen')) {
                echo "Test met file_get_contents: ";
                $result = @file_get_contents($apiUrl, false, $context);
                
                if ($result !== false) {
                    echo "<span class='success'>Succesvol ✅</span><br>";
                    $data = json_decode($result, true);
                    if (isset($data['data']) && count($data['data']) > 0) {
                        echo "API response bevat " . count($data['data']) . " manga items<br>";
                    }
                } else {
                    echo "<span class='error'>Mislukt: " . error_get_last()['message'] . "</span><br>";
                }
            }
            
            // Probeer met cURL
            if (function_exists('curl_init')) {
                echo "Test met cURL: ";
                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $result = curl_exec($ch);
                $error = curl_error($ch);
                
                if ($result !== false) {
                    echo "<span class='success'>Succesvol ✅</span><br>";
                    $data = json_decode($result, true);
                    if (isset($data['data']) && count($data['data']) > 0) {
                        echo "API response bevat " . count($data['data']) . " manga items<br>";
                    }
                } else {
                    echo "<span class='error'>Mislukt: " . $error . "</span><br>";
                }
                
                curl_close($ch);
            }
        } catch (Exception $e) {
            echo "<div class='error'>API Test Fout: " . $e->getMessage() . "</div>";
        }
        ?>
        
        <!-- Bestandsrechten controleren -->
        <h2>Bestandsrechten</h2>
        <?php
        $writeableDirs = ['assets', 'assets/images', '.'];
        
        foreach ($writeableDirs as $dir) {
            $fullPath = __DIR__ . '/' . $dir;
            echo $fullPath . ": ";
            
            if (file_exists($fullPath)) {
                if (is_writable($fullPath)) {
                    echo "<span class='success'>Schrijfbaar ✅</span><br>";
                } else {
                    echo "<span class='error'>Niet schrijfbaar ❌</span><br>";
                }
            } else {
                echo "<span class='error'>Directory niet gevonden ❌</span><br>";
            }
        }
        ?>

        <!-- Algemene errors ophalen -->
        <h2>PHP Error Log (laatste 20 regels)</h2>
        <?php
        // Probeer error log te tonen als die er is
        $errorLog = @file_get_contents(ini_get('error_log'));
        if ($errorLog) {
            // Toon alleen de laatste 20 regels
            $lines = explode("\n", $errorLog);
            $lastLines = array_slice($lines, -20);
            echo "<pre>" . htmlspecialchars(implode("\n", $lastLines)) . "</pre>";
        } else {
            echo "<p>Kan error log niet lezen of deze is leeg.</p>";
        }
        ?>

        <h2>Volgende Stappen</h2>
        <ol>
            <li>Controleer of de database verbinding werkt</li>
            <li>Controleer of de API bereikbaar is</li>
            <li>Als beide werken maar de site nog steeds een 500 error geeft:
                <ul>
                    <li>Check of er meer gedetailleerde foutmeldingen zijn in het error log van de server</li>
                    <li>Controleer of alle bestanden goed geüpload zijn naar Combell</li>
                    <li>Controleer of bestandsrechten correct zijn ingesteld (755 voor directories, 644 voor bestanden)</li>
                </ul>
            </li>
            <li><strong>Belangrijk:</strong> Verwijder deze debug pagina zodra het probleem is opgelost!</li>
        </ol>
    </div>
</body>
</html>
