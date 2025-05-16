-- Create users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create bookmarks table to store manga reading progress
CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `manga_title` varchar(255) NOT NULL,
  `last_chapter` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `api_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_manga` (`user_id`, `manga_title`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create comments table for guestbook functionality
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reads_log table to store detailed reading history
CREATE TABLE `reads_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `manga_title` varchar(255) NOT NULL,
  `chapter` varchar(50) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample user (password: 'password123')
INSERT INTO `users` (`username`, `password_hash`) VALUES
('demo', '$2y$10$AuhhokLyQtGGXB.GfeYVjOXdFCJcH9eAjpXQYhQdE1OklH.rvGyNW');

-- Insert sample manga bookmarks
INSERT INTO `bookmarks` (`user_id`, `manga_title`, `last_chapter`, `notes`) VALUES
(1, 'One Piece', '1080', 'Wano arc was amazing!'),
(1, 'Demon Slayer', '205', 'Completed reading'),
(1, 'My Hero Academia', '397', 'Currently following weekly');

-- Insert sample comments
INSERT INTO `comments` (`user_id`, `name`, `content`, `created_at`) VALUES
(1, 'demo', 'This site is really helping me keep track of all my manga!', NOW()),
(NULL, 'Alice', 'Great idea for a website. I always lose track of where I left off.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(NULL, 'Bob', 'Any recommendations for new manga to read?', DATE_SUB(NOW(), INTERVAL 4 DAY));
