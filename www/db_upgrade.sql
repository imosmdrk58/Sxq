-- First, select the database
USE `manga_tracker`;

-- Add new columns to the bookmarks table
ALTER TABLE `bookmarks`
ADD COLUMN `cover_image` varchar(255) DEFAULT NULL AFTER `notes`,
ADD COLUMN `description` text DEFAULT NULL AFTER `cover_image`,
ADD COLUMN `api_id` varchar(50) DEFAULT NULL AFTER `description`,
ADD COLUMN `api_source` varchar(20) DEFAULT NULL AFTER `api_id`;
