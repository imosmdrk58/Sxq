-- Anilist API Integration Database Upgrade
-- This script adds the necessary column for Anilist API integration

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
-- First check if the index exists
SELECT COUNT(*) INTO @indexExists
FROM INFORMATION_SCHEMA.STATISTICS
WHERE table_schema = 'manga_tracker'
  AND table_name = 'bookmarks'
  AND index_name = 'idx_bookmarks_api';

-- Only create the index if it doesn't exist
SET @createIndexSQL = IF(@indexExists = 0,
    'CREATE INDEX idx_bookmarks_api ON bookmarks(api_id, api_source)',
    'SELECT "Index idx_bookmarks_api already exists" AS message');
PREPARE stmt FROM @createIndexSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
