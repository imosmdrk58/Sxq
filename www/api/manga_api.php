<?php
/**
 * Manga API Integration
 * 
 * This file provides functions to interact with external manga APIs
 * - Primary API: Anilist API (via RapidAPI)
 * - Fallback API: Jikan API (MyAnimeList unofficial API)
 * - Last resort: Mock data when no API is available
 */

// RapidAPI credentials for Anilist
define('RAPIDAPI_KEY', '9b4bb6ea28mshf3e80603ce42097p17e59bjsn3681caf15696');
define('RAPIDAPI_HOST', 'anilist.co');
define('USE_RAPIDAPI', true); // Set to false to force using Jikan API

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
    }
    
    return [
        'data' => $demoManga,
        'pagination' => ['has_next_page' => false, 'current_page' => 1]
    ];
}

/**
 * Search for manga across multiple APIs with fallbacks
 * 
 * This function tries multiple APIs in sequence:
 * 1. First attempt: Anilist API via RapidAPI (if enabled)
 * 2. Second attempt: Jikan API (MyAnimeList unofficial API)
 * 3. Last resort: Mock data if both APIs fail
 * 
 * @param string $title The manga title to search for
 * @param int $limit Maximum number of results to return (default: 10)
 * @return array The manga data in standardized format
 */
function searchMangaByTitle($title, $limit = 10) {
    $apiErrors = [];
    
    // First try Anilist API if enabled
    if (defined('USE_RAPIDAPI') && USE_RAPIDAPI) {
        $anilistResult = searchMangaByTitleAnilist($title, $limit);
        
        // If successful and no error, return the Anilist results
        if ($anilistResult && !isset($anilistResult['error'])) {
            return $anilistResult;
        } else if (isset($anilistResult['error'])) {
            $apiErrors[] = $anilistResult['error'];
        }
    }
    
    // If Anilist failed or is disabled, try Jikan API
    $apiError = null;
    $response = null;
    
    // URL encode the title for the API request
    $encodedTitle = urlencode($title);
    
    // Construct the API URL for searching manga
    $apiUrl = "https://api.jikan.moe/v4/manga?q={$encodedTitle}&limit={$limit}";
    
    // First try with curl if available
    if (function_exists('curl_init')) {
        // Use curl for the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MangaTracker/1.0');
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $apiError = 'Jikan API: cURL error: ' . curl_error($ch);
            $response = null;
        }
        
        curl_close($ch);
    } else {
        $apiError = 'Jikan API: cURL extension not available';
    }
    
    // Try file_get_contents if curl failed or isn't available
    if ($response === null && ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: MangaTracker/1.0'
                ],
                'timeout' => 30
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        
        $context = stream_context_create($opts);
        
        try {
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response === false) {
                $apiError .= ' | file_get_contents failed';
            }
        } catch (Exception $e) {
            $apiError .= ' | ' . $e->getMessage();
            $response = null;
        }
    } else if ($response === null) {
        $apiError .= ' | allow_url_fopen disabled';
    }
    
    // If we got a response, try to decode it
    if ($response !== null) {
        // Decode the JSON response
        $data = json_decode($response, true);
        
        // Check if the JSON decode was successful
        if ($data !== null && is_array($data)) {
            // Add API source to each manga item
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as &$manga) {
                    $manga['api_source'] = 'jikan';
                }
            }
            return $data;
        } else {
            $apiError .= ' | Invalid JSON response';
        }
    }
    
    if ($apiError) {
        $apiErrors[] = $apiError;
    }
    
    // If all APIs failed, return simulated data with error information
    $mockData = generateMockMangaData($title);
    
    // Add error information if available
    if (!empty($apiErrors)) {
        $mockData['api_errors'] = $apiErrors;
    }
    
    return $mockData;
}

/**
 * Get detailed information about a specific manga by its ID
 * 
 * This function supports multiple API sources by checking the api_source parameter
 * and routing to the appropriate API-specific function.
 * 
 * @param int $mangaId The manga ID
 * @param string $apiSource The source API: 'anilist', 'jikan', or blank for auto-detection
 * @return array The manga details in standardized format
 */
function getMangaById($mangaId, $apiSource = '') {
    $apiErrors = [];
    
    // If an API source is specified, route to the appropriate function
    if ($apiSource === 'anilist' || (defined('USE_RAPIDAPI') && USE_RAPIDAPI && $apiSource === '')) {
        $result = getMangaByIdAnilist($mangaId);
        
        // If successful, return the result
        if (!isset($result['error'])) {
            return $result;
        } else {
            $apiErrors[] = $result['error'];
        }
    }
    
    // Try Jikan API if Anilist failed or wasn't specified
    if ($apiSource === 'jikan' || ($apiSource === '' && (!defined('USE_RAPIDAPI') || !USE_RAPIDAPI))) {
        // Construct the API URL for getting manga details
        $apiUrl = "https://api.jikan.moe/v4/manga/{$mangaId}";
        $apiError = null;
        $response = null;
        
        // Try with curl if available
        if (function_exists('curl_init')) {
            // Use curl for the request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'MangaTracker/1.0');
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                $apiError = 'Jikan API: cURL error: ' . curl_error($ch);
                $response = null;
            }
            
            curl_close($ch);
        } else {
            $apiError = 'Jikan API: cURL extension not available';
        }
        
        // Try file_get_contents if curl failed or isn't available
        if ($response === null && ini_get('allow_url_fopen')) {
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: MangaTracker/1.0'
                    ],
                    'timeout' => 30
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];
            
            $context = stream_context_create($opts);
            
            try {
                $response = @file_get_contents($apiUrl, false, $context);
                
                if ($response === false) {
                    $apiError .= ' | file_get_contents failed';
                }
            } catch (Exception $e) {
                $apiError .= ' | ' . $e->getMessage();
                $response = null;
            }
        }
        
        // If we got a response, try to decode it
        if ($response !== null) {
            // Decode the JSON response
            $data = json_decode($response, true);
            
            // Check if the API response is valid
            if ($data && !isset($data['error']) && isset($data['data'])) {
                // Add API source information
                $data['data']['api_source'] = 'jikan';
                return $data;
            } else {
                $apiError .= ' | Invalid or empty response';
            }
        }
        
        if ($apiError) {
            $apiErrors[] = $apiError;
        }
    }
    
    // If all APIs failed, return simulated data
    $mockData = generateMockMangaData('');
    if (!empty($mockData['data'])) {
        // Just return the first mock manga as a single item result
        $result = [
            'data' => $mockData['data'][0],
            'api_source' => 'mock'
        ];
        
        // Add error information if available
        if (!empty($apiErrors)) {
            $result['api_errors'] = $apiErrors;
        }
        
        return $result;
    }
    
    return ['error' => 'Could not get manga details from any API source'];
}

/**
 * Search for manga using the Anilist API via RapidAPI
 * 
 * @param string $title The manga title to search for
 * @param int $limit Maximum number of results to return
 * @return array The manga search results in a standardized format
 */
function searchMangaByTitleAnilist($title, $limit = 10) {
    $apiError = null;
    $response = null;
    
    // GraphQL query for Anilist
    $query = '{
        Page(page: 1, perPage: ' . $limit . ') {
            media(search: "' . addslashes($title) . '", type: MANGA, sort: POPULARITY_DESC) {
                id
                title {
                    romaji
                    english
                    native
                }
                description
                chapters
                volumes
                status
                averageScore
                genres
                coverImage {
                    large
                    medium
                }
                startDate {
                    year
                    month
                    day
                }
                endDate {
                    year
                    month
                    day
                }
            }
        }
    }';
    
    // RapidAPI endpoint
    $apiUrl = 'https://anilist.co/api/v2/';
    
    // First try with curl if available
    if (function_exists('curl_init')) {
        // Use curl for the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-RapidAPI-Key: ' . RAPIDAPI_KEY,
            'X-RapidAPI-Host: ' . RAPIDAPI_HOST
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $apiError = 'cURL error: ' . curl_error($ch);
            $response = null;
        }
        
        curl_close($ch);
    } else {
        $apiError = 'cURL extension not available';
    }
    
    // If curl failed, try file_get_contents if allowed
    if ($response === null && ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'X-RapidAPI-Key: ' . RAPIDAPI_KEY,
                    'X-RapidAPI-Host: ' . RAPIDAPI_HOST
                ],
                'content' => json_encode(['query' => $query]),
                'timeout' => 30
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        
        $context = stream_context_create($opts);
        
        try {
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response === false) {
                $apiError .= ' | file_get_contents failed';
            }
        } catch (Exception $e) {
            $apiError .= ' | ' . $e->getMessage();
            $response = null;
        }
    } else if ($response === null && $apiError !== 'cURL extension not available') {
        $apiError .= ' | allow_url_fopen disabled';
    }
    
    // If we got a response, try to decode it
    if ($response !== null) {
        // Decode the JSON response
        $data = json_decode($response, true);
        
        // Check if the JSON decode was successful
        if ($data !== null && is_array($data) && !empty($data['data']['Page']['media'])) {
            // Convert Anilist format to our standard format
            return convertAnilistToStandardFormat($data);
        } else {
            $apiError .= ' | Invalid or empty Anilist response';
        }
    }
      // If the Anilist API fails, return an empty array with an error to indicate a fallback is needed
    return ['error' => 'Failed to fetch manga from Anilist API: ' . $apiError];
}

/**
 * Convert Anilist API response to standardized format
 * 
 * @param array $anilistData The raw Anilist API response
 * @return array Standardized manga data format
 */
function convertAnilistToStandardFormat($anilistData) {
    $standardData = ['data' => [], 'pagination' => ['has_next_page' => false, 'current_page' => 1]];
    
    foreach ($anilistData['data']['Page']['media'] as $manga) {
        // Get the best available title
        $title = $manga['title']['english'] ?: $manga['title']['romaji'] ?: $manga['title']['native'] ?: 'Unknown Title';
        
        // Format the published date range
        $published = '';
        if (!empty($manga['startDate']['year'])) {
            $published = $manga['startDate']['year'];
            if (!empty($manga['endDate']['year'])) {
                $published .= ' to ' . $manga['endDate']['year'];
            } else {
                $published .= ' to Present';
            }
        }
        
        // Convert genres array to the format expected by our application
        $genres = [];
        foreach ($manga['genres'] as $genreName) {
            $genres[] = ['name' => $genreName];
        }
        
        // Add the formatted manga data to our result
        $standardData['data'][] = [
            'title' => $title,
            'synopsis' => $manga['description'] ?: 'No description available',
            'score' => $manga['averageScore'] / 10, // Anilist uses 0-100, we use 0-10
            'chapters' => $manga['chapters'] ?: 0,
            'published' => ['string' => $published],
            'images' => ['jpg' => ['image_url' => $manga['coverImage']['large'] ?: 'assets/images/no-cover.png']],
            'genres' => $genres,
            'api_id' => $manga['id'],
            'api_source' => 'anilist'
        ];
    }
    
    return $standardData;
}

/**
 * Get detailed manga information from Anilist API
 * 
 * @param int $mangaId The manga ID from the Anilist API
 * @return array The manga details in standardized format
 */
function getMangaByIdAnilist($mangaId) {
    $apiError = null;
    $response = null;
    
    // GraphQL query for Anilist
    $query = '{
        Media(id: ' . intval($mangaId) . ', type: MANGA) {
            id
            title {
                romaji
                english
                native
            }
            description
            chapters
            volumes
            status
            averageScore
            genres
            coverImage {
                large
                medium
            }
            startDate {
                year
                month
                day
            }
            endDate {
                year
                month
                day
            }
            tags {
                name
            }
            staff {
                edges {
                    node {
                        name {
                            full
                        }
                        role
                    }
                }
            }
        }
    }';
    
    // RapidAPI endpoint
    $apiUrl = 'https://anilist.co/api/v2/';
    
    // Try with curl if available
    if (function_exists('curl_init')) {
        // Use curl for the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-RapidAPI-Key: ' . RAPIDAPI_KEY,
            'X-RapidAPI-Host: ' . RAPIDAPI_HOST
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $apiError = 'cURL error: ' . curl_error($ch);
            $response = null;
        }
        
        curl_close($ch);
    } else {
        $apiError = 'cURL extension not available';
    }
    
    // If curl failed, try file_get_contents if allowed
    if ($response === null && ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'X-RapidAPI-Key: ' . RAPIDAPI_KEY,
                    'X-RapidAPI-Host: ' . RAPIDAPI_HOST
                ],
                'content' => json_encode(['query' => $query]),
                'timeout' => 30
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        
        $context = stream_context_create($opts);
        
        try {
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response === false) {
                $apiError .= ' | file_get_contents failed';
            }
        } catch (Exception $e) {
            $apiError .= ' | ' . $e->getMessage();
            $response = null;
        }
    }
    
    // If we got a response, try to decode it
    if ($response !== null) {
        // Decode the JSON response
        $data = json_decode($response, true);
        
        // Check if the API response is valid
        if ($data && !empty($data['data']['Media'])) {
            $manga = $data['data']['Media'];
            
            // Get the best available title
            $title = $manga['title']['english'] ?: $manga['title']['romaji'] ?: $manga['title']['native'] ?: 'Unknown Title';
            
            // Format the published date range
            $published = '';
            if (!empty($manga['startDate']['year'])) {
                $published = $manga['startDate']['year'];
                if (!empty($manga['endDate']['year'])) {
                    $published .= ' to ' . $manga['endDate']['year'];
                } else {
                    $published .= ' to Present';
                }
            }
            
            // Convert genres array to the format expected by our application
            $genres = [];
            foreach ($manga['genres'] as $genreName) {
                $genres[] = ['name' => $genreName];
            }
            
            // Process staff/authors information
            $authors = [];
            if (!empty($manga['staff']['edges'])) {
                foreach ($manga['staff']['edges'] as $edge) {
                    if (!empty($edge['node']['name']['full'])) {
                        $authors[] = $edge['node']['name']['full'] . ' (' . $edge['node']['role'] . ')';
                    }
                }
            }
            
            // Return standardized format
            return [
                'data' => [
                    'title' => $title,
                    'synopsis' => $manga['description'] ?: 'No description available',
                    'score' => $manga['averageScore'] / 10, // Anilist uses 0-100, we use 0-10
                    'chapters' => $manga['chapters'] ?: 0,
                    'published' => ['string' => $published],
                    'images' => ['jpg' => ['image_url' => $manga['coverImage']['large'] ?: 'assets/images/no-cover.png']],
                    'genres' => $genres,
                    'authors' => $authors,
                    'api_id' => $manga['id'],
                    'api_source' => 'anilist'
                ]
            ];
        }
    }
    
    // If the API request failed, return an error
    return ['error' => 'Failed to fetch manga details from Anilist: ' . $apiError];
}
