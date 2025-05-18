<?php
/**
 * Manga Utility Functions
 * This file provides shared functions for manga operations across the application.
 */

// Include API functions if not already included
if (!function_exists('searchMangaByTitle')) {
    require_once __DIR__ . '/../api/simplified_manga_api.php';
}

/**
 * Get manga cover image from API based on title
 * 
 * @param string $title The manga title to search for
 * @return string The URL of the cover image, or empty string if not found
 */
function getMangaCoverFromAPI($title) {
    try {
        $results = searchMangaByTitle($title, 1);
        if (!empty($results['results']) && isset($results['results'][0]['images']['jpg']['image_url'])) {
            return $results['results'][0]['images']['jpg']['image_url'];
        }
    } catch (Exception $e) {
        // Silent fail - just return empty if API fails
    }
    return '';
}

/**
 * Filter manga search results to remove inappropriate content
 * 
 * @param array $results The manga search results to filter
 * @return array Filtered manga search results
 */
function filterInappropriateContent($results) {
    $filteredResults = [];
    $adultKeywords = ['hentai', 'adult', 'explicit', 'ecchi', 'smut', 'yaoi', 'yuri'];
    
    foreach ($results as $manga) {
        // Skip manga with adult keywords in title
        $containsAdultKeyword = false;
        foreach ($adultKeywords as $keyword) {
            if (stripos($manga['title'], $keyword) !== false) {
                $containsAdultKeyword = true;
                break;
            }
        }
        
        // Also check genres if available
        $adultGenres = false;
        if (isset($manga['genres']) && is_array($manga['genres'])) {
            foreach ($manga['genres'] as $genre) {
                if (in_array(strtolower($genre['name']), $adultKeywords)) {
                    $adultGenres = true;
                    break;
                }
            }
        }
        
        if (!$containsAdultKeyword && !$adultGenres) {
            $filteredResults[] = $manga;
        }
    }
    
    return $filteredResults;
}

/**
 * Get detailed manga information from API
 * 
 * @param string $title The manga title to search for
 * @return array|null Array of manga details or null if not found
 */
function getMangaDetailsFromAPI($title) {
    try {
        $results = searchMangaByTitle($title, 1);
        if (!empty($results['results'])) {
            return $results['results'][0];
        }
    } catch (Exception $e) {
        // Silent fail - just return null if API fails
    }
    return null;
}

/**
 * Update manga cover image in database if not already set
 * 
 * @param PDO $pdo Database connection
 * @param int $mangaId The bookmark ID
 * @param string $mangaTitle The manga title
 * @return bool True if update was successful, false otherwise
 */
function updateMangaCoverIfEmpty($pdo, $mangaId, $mangaTitle) {
    try {
        // First check if the manga already has a cover
        $checkStmt = $pdo->prepare("SELECT cover_image FROM bookmarks WHERE id = ? LIMIT 1");
        $checkStmt->execute([$mangaId]);
        $currentCover = $checkStmt->fetchColumn();
        
        // If cover is empty, try to update it
        if (empty($currentCover)) {
            $coverImage = getMangaCoverFromAPI($mangaTitle);
            
            if (!empty($coverImage)) {
                $updateStmt = $pdo->prepare("UPDATE bookmarks SET cover_image = ? WHERE id = ?");
                return $updateStmt->execute([$coverImage, $mangaId]);
            }
        }
    } catch (Exception $e) {
        // Silent fail
    }
    
    return false;
}

/**
 * Get popular manga with cover images
 * 
 * @param PDO $pdo Database connection
 * @param int $limit Number of manga to return
 * @return array Array of popular manga with cover images
 */
function getPopularMangaWithCovers($pdo, $limit = 3) {
    // Get popular manga
    $popularStmt = $pdo->query("
        SELECT manga_title, COUNT(*) as bookmark_count,
               MAX(cover_image) as cover_image, MAX(api_id) as api_id, MAX(api_source) as api_source
        FROM bookmarks 
        GROUP BY manga_title 
        ORDER BY bookmark_count DESC, manga_title ASC
        LIMIT $limit
    ");
    
    $popularManga = $popularStmt->fetchAll();
    
    // Add cover images for manga that don't have one
    foreach ($popularManga as &$manga) {
        if (empty($manga['cover_image'])) {
            $manga['cover_image'] = getMangaCoverFromAPI($manga['manga_title']);
        }
    }
    
    return $popularManga;
}
