<?php
/**
 * Simplified Manga API Integration
 * 
 * This file provides functions to interact with the Jikan API (MyAnimeList unofficial API)
 * It has been simplified to use only the direct Jikan API which is confirmed to work
 */

// Detect if cURL is available or not
define('CURL_AVAILABLE', function_exists('curl_init'));

// Define Jikan API base URL
define('JIKAN_API_BASE', 'https://api.jikan.moe/v4');

// Use mock data only if cURL is not available
define('USE_MOCK_DATA', !CURL_AVAILABLE);

/**
 * Generate mock manga data for demo purposes when API access isn't available
 * 
 * @param string $title The search term to simulate results for
 * @return array Simulated manga data structure
 */
function generateMockMangaData($title) {
    // List of demo manga to show when API is unavailable
    $demoManga = [
        [
            'title' => 'Naruto',
            'synopsis' => 'Naruto Uzumaki, a mischievous adolescent ninja, struggles as he searches for recognition and dreams of becoming the Hokage, the village\'s leader and strongest ninja.',
            'score' => 8.07,
            'chapters' => 700,
            'published' => ['string' => '1999 to 2014'],
            'images' => ['jpg' => ['image_url' => 'assets/images/no-cover.png']],
            'genres' => [
                ['name' => 'Action'], 
                ['name' => 'Adventure'],
                ['name' => 'Martial Arts']
            ]
        ],
        [
            'title' => 'One Piece',
            'synopsis' => 'Follows the adventures of Monkey D. Luffy and his pirate crew as they explore the Grand Line in search of the "One Piece" treasure.',
            'score' => 9.15,
            'chapters' => 1080,
            'published' => ['string' => '1997 to Present'],
            'images' => ['jpg' => ['image_url' => 'assets/images/no-cover.png']],
            'genres' => [
                ['name' => 'Action'], 
                ['name' => 'Adventure'],
                ['name' => 'Fantasy']
            ]
        ],
        [
            'title' => 'Demon Slayer',
            'synopsis' => 'After a demon attack leaves his family slain and his sister cursed, Tanjiro embarks upon a perilous journey to find a cure and avenge his family.',
            'score' => 8.73,
            'chapters' => 205,
            'published' => ['string' => '2016 to 2020'],
            'images' => ['jpg' => ['image_url' => 'assets/images/no-cover.png']],
            'genres' => [
                ['name' => 'Action'], 
                ['name' => 'Supernatural'],
                ['name' => 'Historical']
            ]
        ]
    ];
    
    // Add a notification to the description that this is offline data
    foreach ($demoManga as &$manga) {
        $manga['synopsis'] = "[OFFLINE MODE - API UNAVAILABLE] " . $manga['synopsis'];
        $manga['api_source'] = 'mock';
    }
    
    return [
        'data' => $demoManga,
        'pagination' => ['has_next_page' => false, 'current_page' => 1]
    ];
}

/**
 * Search for manga by title using the Jikan API
 * 
 * @param string $title The manga title to search for
 * @param int $limit Maximum number of results to return (default: 10)
 * @return array The manga data in standardized format
 */
function searchMangaByTitle($title, $limit = 10) {
    // Use mock data if configured to do so
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA) {
        return [
            'results' => generateMockMangaData($title)['data'],
            'source' => 'mock'
        ];
    }

    // URL encode the title for the API request
    $encodedTitle = urlencode($title);
    
    // Use direct Jikan API
    $apiUrl = JIKAN_API_BASE . "/manga?q={$encodedTitle}&limit={$limit}";
    
    // Make the request
    $result = makeHttpRequest($apiUrl);
    
    // Process the response
    if ($result['success']) {
        try {
            $data = json_decode($result['data'], true);
            
            if (!$data || isset($data['error']) || !isset($data['data'])) {
                $error = isset($data['error']) ? 'Jikan API error: ' . $data['error'] : 'Invalid response from Jikan API';
                return [
                    'results' => generateMockMangaData($title)['data'],
                    'source' => 'mock',
                    'errors' => [$error]
                ];
            }
            
            // Process each manga result
            $manga_results = [];
            foreach ($data['data'] as $manga) {
                $manga_results[] = [
                    'api_id' => $manga['mal_id'],
                    'api_source' => 'jikan',
                    'title' => $manga['title'],
                    'original_title' => isset($manga['title_japanese']) ? $manga['title_japanese'] : null,
                    'romaji_title' => null,
                    'synopsis' => isset($manga['synopsis']) ? $manga['synopsis'] : 'No description available',
                    'score' => isset($manga['score']) ? $manga['score'] : null,
                    'chapters' => isset($manga['chapters']) ? $manga['chapters'] : null,
                    'volumes' => isset($manga['volumes']) ? $manga['volumes'] : null,
                    'status' => isset($manga['status']) ? $manga['status'] : null,
                    'published' => isset($manga['published']) ? $manga['published'] : ['string' => 'Unknown'],
                    'images' => isset($manga['images']) ? $manga['images'] : ['jpg' => ['image_url' => 'assets/images/no-cover.png']],
                    'genres' => isset($manga['genres']) ? $manga['genres'] : []
                ];
            }
            
            return [
                'results' => $manga_results,
                'source' => 'jikan'
            ];
        } catch (Exception $e) {
            return [
                'results' => generateMockMangaData($title)['data'],
                'source' => 'mock',
                'errors' => ['Failed to parse Jikan API response: ' . $e->getMessage()]
            ];
        }
    } else {
        return [
            'results' => generateMockMangaData($title)['data'],
            'source' => 'mock',
            'errors' => ['Jikan API request failed: ' . $result['error']]
        ];
    }
}

/**
 * Get detailed information about a specific manga by its ID
 * 
 * @param int $mangaId The manga ID from MyAnimeList
 * @param string $apiSource The source API (ignored in this simplified version)
 * @return array The manga details in standardized format
 */
function getMangaById($mangaId, $apiSource = '') {
    // Use mock data if configured to do so
    if (defined('USE_MOCK_DATA') && USE_MOCK_DATA) {
        $mockData = generateMockMangaData('');
        return [
            'data' => $mockData['data'][0],
            'source' => 'mock'
        ];
    }
    
    // Direct Jikan API access
    $apiUrl = JIKAN_API_BASE . "/manga/{$mangaId}";
    
    // Make the request
    $result = makeHttpRequest($apiUrl);
    
    if ($result['success']) {
        try {
            // Decode the JSON response
            $data = json_decode($result['data'], true);
            
            // Check if the API response is valid
            if ($data && !isset($data['error']) && isset($data['data'])) {
                // Add API source information
                $data['data']['api_source'] = 'jikan';
                return $data;
            } else {
                $error = isset($data['error']) ? 'Jikan API error: ' . $data['error'] : 'Invalid or empty Jikan API response';
                
                // Fallback to mock data
                $mockData = generateMockMangaData('');
                return [
                    'data' => $mockData['data'][0],
                    'source' => 'mock',
                    'api_errors' => [$error]
                ];
            }
        } catch (Exception $e) {
            // Fallback to mock data
            $mockData = generateMockMangaData('');
            return [
                'data' => $mockData['data'][0],
                'source' => 'mock',
                'api_errors' => ['Failed to parse Jikan API response: ' . $e->getMessage()]
            ];
        }
    } else {
        // Fallback to mock data
        $mockData = generateMockMangaData('');
        return [
            'data' => $mockData['data'][0],
            'source' => 'mock',
            'api_errors' => ['Jikan API request failed: ' . $result['error']]
        ];
    }
}

/**
 * Make an HTTP request with multiple fallback options
 * 
 * @param string $url The URL to request
 * @param array $headers Optional request headers
 * @param string $method HTTP method (GET, POST)
 * @param string $data POST data if applicable
 * @return array Response with 'success', 'data', and 'error' keys
 */
function makeHttpRequest($url, $headers = [], $method = 'GET', $data = null) {
    $result = [
        'success' => false,
        'data' => null,
        'error' => null
    ];
    
    // Try cURL first if available
    if (function_exists('curl_init')) {
        try {
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable hostname verification
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);    // Long timeout
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);           // Long timeout
            
            // Use a CA certificate file if available in the project directory
            $caFile = dirname(dirname(__FILE__)) . '/cacert.pem';
            if (file_exists($caFile)) {
                curl_setopt($ch, CURLOPT_CAINFO, $caFile);
            }
            
            if ($method === 'POST' && $data) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($response === false) {
                $result['error'] = 'cURL error: ' . curl_error($ch);
            } else {
                $result['success'] = ($httpCode >= 200 && $httpCode < 300);
                $result['data'] = $response;
                if (!$result['success']) {
                    $result['error'] = 'HTTP error: ' . $httpCode;
                }
            }
            
            curl_close($ch);
            
            if ($result['success']) {
                return $result;
            }
        } catch (Exception $e) {
            $result['error'] = 'cURL exception: ' . $e->getMessage();
            // Continue to fallback methods
        }
    }
    
    // Try file_get_contents if cURL failed or is not available
    if (ini_get('allow_url_fopen')) {
        try {
            // Format headers correctly for file_get_contents
            $headerStr = '';
            if (!empty($headers)) {
                // Convert headers array to proper string format
                foreach ($headers as $header) {
                    $headerStr .= $header . "\r\n";
                }
            }
            
            // Add a default user agent if none provided
            if (strpos($headerStr, 'User-Agent:') === false) {
                $headerStr .= "User-Agent: MangaTracker/1.0\r\n";
            }
            
            $opts = [
                'http' => [
                    'method' => $method,
                    'header' => $headerStr,
                    'timeout' => 30,
                    'ignore_errors' => true // Don't fail on non-200 responses
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ];
            
            // Add content for POST requests
            if ($method === 'POST' && $data) {
                $opts['http']['content'] = $data;
                
                // Make sure we have the right content type for POSTs
                if (strpos($headerStr, 'Content-Type:') === false) {
                    $opts['http']['header'] .= "Content-Type: application/json\r\n";
                }
            }
            
            $context = stream_context_create($opts);
            
            // Use error suppression to prevent warnings, but capture the actual error
            $response = @file_get_contents($url, false, $context);
            $error = error_get_last();
            
            if ($response !== false) {
                // Get HTTP response headers to check status
                $statusLine = isset($http_response_header[0]) ? $http_response_header[0] : '';
                if (strpos($statusLine, '200') !== false || strpos($statusLine, '2') !== false) {
                    $result['success'] = true;
                    $result['data'] = $response;
                    return $result;
                } else {
                    $result['error'] = 'HTTP error: ' . $statusLine;
                    $result['data'] = $response; // Include response for debugging
                }
            } else {
                $errorMsg = ($error !== null) ? $error['message'] : 'Unknown error';
                $result['error'] = 'file_get_contents failed: ' . $errorMsg;
            }
        } catch (Exception $e) {
            $result['error'] = 'file_get_contents exception: ' . $e->getMessage();
        }
    }
    
    return $result;
}
?>
