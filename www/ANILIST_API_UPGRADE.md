# Anilist API Integration

This document describes the integration of the Anilist API via RapidAPI into the Manga Tracker application.

## Overview

The application now supports multiple manga data sources with a fallback mechanism:

1. **Primary API**: Anilist API via RapidAPI
2. **Secondary API**: Jikan API (MyAnimeList unofficial API)
3. **Last Resort**: Mock data when no API is available

## New Features

1. **API Source Selection**: Users can now choose which API to use when searching for manga
   - Auto (default): Tries all APIs in sequence
   - Anilist: Uses only the Anilist API
   - Jikan: Uses only the Jikan API

2. **API Badges**: Each manga result displays a badge indicating which API provided the data

3. **Enhanced Database Schema**: Added `api_source` column to track which API each manga came from

## API Configuration

The API settings can be found at the top of the `api/manga_api.php` file:

```php
// RapidAPI credentials for Anilist
define('RAPIDAPI_KEY', '9b4bb6ea28mshf3e80603ce42097p17e59bjsn3681caf15696');
define('RAPIDAPI_HOST', 'anilist.co');
define('USE_RAPIDAPI', true); // Set to false to force using Jikan API
```

You can disable the Anilist API by setting `USE_RAPIDAPI` to `false`.

## Installation and Upgrade Instructions

### 1. Database Upgrade

Run the following SQL script to update your database:

```sql
-- First, select the database
USE `manga_tracker`;

-- Check if the api_source column exists, if not add it
SET @columnExists = 0;
SELECT COUNT(*) INTO @columnExists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'manga_tracker' AND TABLE_NAME = 'bookmarks' AND COLUMN_NAME = 'api_source';

SET @addColumnSQL = IF(@columnExists = 0, 
    'ALTER TABLE `bookmarks` ADD COLUMN `api_source` varchar(20) DEFAULT NULL AFTER `api_id`', 
    'SELECT "Column api_source already exists" AS message');
PREPARE stmt FROM @addColumnSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create an index to improve performance when searching by API ID and source
CREATE INDEX IF NOT EXISTS idx_bookmarks_api 
ON bookmarks(api_id, api_source);
```

This can be done by running the `db_upgrade_anilist.sql` script.

### 2. API Configuration

Edit the `api/manga_api.php` file to configure your RapidAPI key:

```php
// RapidAPI credentials for Anilist
define('RAPIDAPI_KEY', 'your-rapidapi-key');
define('RAPIDAPI_HOST', 'anilist.co');
define('USE_RAPIDAPI', true); // Set to false to force using Jikan API
```

Replace `'your-rapidapi-key'` with your actual RapidAPI key.

### 3. Testing the API

1. Navigate to the "Browse" page
2. Search for a manga title
3. Observe the API badges showing which API provided each result
4. Try the different API source options (Auto, Anilist, Jikan)

## Functions

### Main Functions

These are the main functions you'll use in your application:

1. **searchMangaByTitle($title, $limit = 10)**
   - Searches for manga across all available APIs
   - Returns standardized manga data regardless of which API is used
   - Tries Anilist first, then Jikan, then falls back to mock data

2. **getMangaById($mangaId, $apiSource = '')**
   - Gets detailed information about a specific manga
   - `$apiSource` can be 'anilist', 'jikan', or empty for auto-detection
   - Returns standardized manga data regardless of which API is used

### API-Specific Functions

These functions are used internally but can also be called directly if needed:

1. **searchMangaByTitleAnilist($title, $limit = 10)**
   - Searches for manga using the Anilist API
   - Returns data in standardized format

2. **getMangaByIdAnilist($mangaId)**
   - Gets detailed information about a specific manga from Anilist API
   - Returns data in standardized format

3. **generateMockMangaData($title)**
   - Generates mock manga data when no API is available
   - Used as a last resort fallback

## Standard Data Format

All API functions return data in the same standardized format:

```php
[
    'data' => [
        [
            'title' => 'Manga Title',
            'synopsis' => 'Description of the manga',
            'score' => 8.5, // Rating out of 10
            'chapters' => 200, // Total chapters
            'published' => ['string' => '2010 to 2015'],
            'images' => ['jpg' => ['image_url' => 'path/to/cover.jpg']],
            'genres' => [
                ['name' => 'Action'],
                ['name' => 'Adventure']
            ],
            'api_id' => '12345', // ID in the source API
            'api_source' => 'anilist' // Source API: 'anilist', 'jikan', or 'mock'
        ],
        // More manga results...
    ],
    'pagination' => [
        'has_next_page' => false,
        'current_page' => 1
    ]
]
```

## Usage Examples

### Search for Manga

```php
// Search across all available APIs
$results = searchMangaByTitle('Naruto', 5);

// Display results
foreach ($results['data'] as $manga) {
    echo '<div class="manga">';
    echo '<h3>' . htmlspecialchars($manga['title']) . '</h3>';
    echo '<img src="' . htmlspecialchars($manga['images']['jpg']['image_url']) . '" alt="Cover">';
    echo '<p>' . htmlspecialchars($manga['synopsis']) . '</p>';
    echo '</div>';
}
```

### Get Manga Details

```php
// Get manga details with auto-detection of API source
$manga = getMangaById('12345');

// Or specify the API source
$manga = getMangaById('12345', 'anilist');

// Display manga details
$data = $manga['data'];
echo '<h2>' . htmlspecialchars($data['title']) . '</h2>';
echo '<img src="' . htmlspecialchars($data['images']['jpg']['image_url']) . '" alt="Cover">';
echo '<p>' . htmlspecialchars($data['synopsis']) . '</p>';
```

## Troubleshooting

If you encounter issues with the API:

1. **Check API Key**: Ensure your RapidAPI key is correct
2. **Test API Connectivity**: Use the `USE_RAPIDAPI` constant to switch between APIs
3. **Error Messages**: API errors are captured in `$result['api_errors']`
4. **Fallback Mechanism**: The system will automatically fall back to available APIs

## API Source Types

The application now tracks manga source using the following identifiers:

1. **anilist**: Data retrieved from Anilist API via RapidAPI
2. **jikan**: Data retrieved from the Jikan API (MyAnimeList)
3. **mock**: Data generated locally when no API is available
4. **manual**: Data entered manually by the user

Each source is visually indicated with a different colored badge next to the manga title.

## Credits

- **Anilist API**: Provided via RapidAPI (https://rapidapi.com/anilist/api/anilist)
- **Jikan API**: Unofficial MyAnimeList API (https://jikan.moe/)
