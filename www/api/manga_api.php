<?php
/**
 * Manga API Integration
 * 
 * This file provides functions to interact with external manga APIs
 */

/**
 * Get manga details from Jikan API (Unofficial MyAnimeList API)
 * 
 * @param string $title The manga title to search for
 * @param int $limit Maximum number of results to return (default: 10)
 * @return array The manga data
 */
function searchMangaByTitle($title, $limit = 10) {
    // URL encode the title for the API request
    $encodedTitle = urlencode($title);
    
    // Construct the API URL for searching manga
    $apiUrl = "https://api.jikan.moe/v4/manga?q={$encodedTitle}&limit={$limit}";
    
    // Make the API request with a 2-second timeout
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MangaTracker/1.0');
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => 'API request failed: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    
    // Decode the JSON response
    $data = json_decode($response, true);
    
    // Check if the API response is valid
    if (!$data || isset($data['error'])) {
        return ['error' => 'Failed to fetch manga data or invalid response'];
    }
    
    return $data;
}

/**
 * Get detailed information about a specific manga by its ID
 * 
 * @param int $mangaId The manga ID from the Jikan/MyAnimeList API
 * @return array The manga details
 */
function getMangaById($mangaId) {
    // Construct the API URL for getting manga details
    $apiUrl = "https://api.jikan.moe/v4/manga/{$mangaId}";
    
    // Make the API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MangaTracker/1.0');
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => 'API request failed: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    
    // Decode the JSON response
    $data = json_decode($response, true);
    
    // Check if the API response is valid
    if (!$data || isset($data['error'])) {
        return ['error' => 'Failed to fetch manga details or invalid response'];
    }
    
    return $data;
}
