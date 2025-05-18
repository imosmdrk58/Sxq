<?php
/**
 * Manga API adapter for compatibility
 * This file redirects to the simplified_manga_api.php which is confirmed to work on Combell
 */

// Define required constants to prevent undefined constant errors
define('JIKAN_RAPIDAPI_HOST', 'jikan1.p.rapidapi.com');
define('ANILIST_RAPIDAPI_HOST', 'anilist-graphql.p.rapidapi.com');
define('USE_RAPIDAPI', false);  // Set to false to use direct Jikan API

// Include the working simplified API
require_once __DIR__ . '/simplified_manga_api.php';

// Add compatibility function for Anilist if it doesn't exist in simplified API
if (!function_exists('searchMangaByTitleAnilist')) {
    function searchMangaByTitleAnilist($title, $limit = 10) {
        // Redirect to the standard search function with fallbacks
        return searchMangaByTitle($title, $limit);
    }
}
?>
