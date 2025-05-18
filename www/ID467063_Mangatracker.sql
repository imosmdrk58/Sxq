-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: com-linweb997.srv.combell-ops.net:3306
-- Gegenereerd op: 18 mei 2025 om 14:08
-- Serverversie: 8.0.36-28
-- PHP-versie: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ID467063_Mangatracker1`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `manga_title` varchar(255) NOT NULL,
  `last_chapter` varchar(50) NOT NULL,
  `max_chapters` int DEFAULT NULL,
  `notes` text,
  `cover_image` varchar(255) DEFAULT NULL,
  `description` text,
  `api_id` varchar(50) DEFAULT NULL,
  `api_source` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Gegevens worden geëxporteerd voor tabel `bookmarks`
--

INSERT INTO `bookmarks` (`id`, `user_id`, `manga_title`, `last_chapter`, `max_chapters`, `notes`, `cover_image`, `description`, `api_id`, `api_source`, `created_at`, `updated_at`) VALUES
(23, 4, 'Uchiha Sasuke no Sharingan Den', '26', 26, '', 'https://cdn.myanimelist.net/images/manga/3/181265.jpg', 'A spin-off manga series about the character Uchiha Sasuke from the series Naruto. The series shows a more humorous side to the normally cool and aloof Sasuke.\r\n\r\n(Source: Saiyan Island)', '77273', 'jikan', '2025-05-18 08:44:41', '2025-05-18 08:45:14'),
(28, 3, 'Nazo no Kanojo X', '96', 96, '', 'https://cdn.myanimelist.net/images/manga/1/260098.jpg', 'Akira Tsubaki is a normal high schooler who sits next to new transfer student Mikoto Urabe during classes. Her face half hidden by hair, Urabe earned a reputation as a weirdo on her first day of school when she started laughing loudly for no apparent reason in the middle of a class. She does not like to interact with her classmates and instead prefers to nap on her desk. \r\n\r\nOne day after school, Tsubaki finds Urabe sleeping on her desk and wakes her up. However, after Urabe goes home, he notices her drool on her desk and impulsively tastes it. A few days later, he is struck down by a fever and is visited by Urabe at his home. After confirming the fact that Tsubaki tasted her drool that day, she informs him that his fever is nothing but love sickness and that he is suffering from withdrawal symptoms of her drool. Thus, the two enter into a strange relationship where Urabe feeds Tsubaki her drool everyday to prevent any more sickness.\r\n\r\n[Written by MAL Rewrite]', '1926', 'jikan', '2025-05-18 09:25:08', '2025-05-18 09:25:08'),
(29, 5, 'Naruto', '700', 700, '', 'https://cdn.myanimelist.net/images/manga/3/249658.jpg', 'Whenever Naruto Uzumaki proclaims that he will someday become the Hokage—a title bestowed upon the best ninja in the Village Hidden in the Leaves—no one takes him seriously. Since birth, Naruto has been shunned and ridiculed by his fellow villagers. But their contempt isn\'t because Naruto is loud-mouthed, mischievous, or because of his ineptitude in the ninja arts, but because there is a demon inside him. Prior to Naruto\'s birth, the powerful and deadly Nine-Tailed Fox attacked the village. In order to stop the rampage, the Fourth Hokage sacrificed his life to seal the demon inside the body of the newborn Naruto.\r\n\r\nAnd so when he is assigned to Team 7—along with his new teammates Sasuke Uchiha and Sakura Haruno, under the mentorship of veteran ninja Kakashi Hatake—Naruto is forced to work together with other people for the first time in his life. Through undergoing vigorous training and taking on challenging missions, Naruto must learn what it means to work in a team and carve his own route toward becoming a full-fledged ninja recognized by his village.\r\n\r\n[Written by MAL Rewrite]', '11', 'jikan', '2025-05-18 09:43:37', '2025-05-18 09:43:37'),
(30, 3, 'Naruto', '700', 700, '', 'https://cdn.myanimelist.net/images/manga/3/249658.jpg', 'Whenever Naruto Uzumaki proclaims that he will someday become the Hokage—a title bestowed upon the best ninja in the Village Hidden in the Leaves—no one takes him seriously. Since birth, Naruto has been shunned and ridiculed by his fellow villagers. But their contempt isn\'t because Naruto is loud-mouthed, mischievous, or because of his ineptitude in the ninja arts, but because there is a demon inside him. Prior to Naruto\'s birth, the powerful and deadly Nine-Tailed Fox attacked the village. In order to stop the rampage, the Fourth Hokage sacrificed his life to seal the demon inside the body of the newborn Naruto.\r\n\r\nAnd so when he is assigned to Team 7—along with his new teammates Sasuke Uchiha and Sakura Haruno, under the mentorship of veteran ninja Kakashi Hatake—Naruto is forced to work together with other people for the first time in his life. Through undergoing vigorous training and taking on challenging missions, Naruto must learn what it means to work in a team and carve his own route toward becoming a full-fledged ninja recognized by his village.\r\n\r\n[Written by MAL Rewrite]', '11', 'jikan', '2025-05-18 09:43:54', '2025-05-18 09:43:54'),
(31, 3, 'Romeo', '1', NULL, '', 'https://cdn.myanimelist.net/images/manga/1/190687.jpg', 'Kouyou is a Lycan who possesses some of the most potent pheromones among his kind. His pheromones are strong enough to seduce any beastman, any beastman except Jade that is. Though Kouyou tries his best to seduce the man, Jade remains resolute in his decision and refuses to give in to the sweet allure of the charming beast. Just what does Kouyou have to do to make this unyielding beast fall for him?\r\n\r\n(Source: Digital Manga Publishing)', '103606', 'jikan', '2025-05-18 11:12:10', '2025-05-18 11:12:22'),
(33, 3, 'Deathtament: Shin Megami Tensei DSJ Another Report', '17', NULL, '', 'https://cdn.myanimelist.net/images/manga/2/250837.jpg', NULL, NULL, 'manual', '2025-05-18 11:12:29', '2025-05-18 11:12:29'),
(38, 6, 'Lol', '5', 50, '', 'https://cdn.myanimelist.net/images/manga/2/195873.jpg', NULL, NULL, 'manual', '2025-05-18 11:54:16', '2025-05-18 11:54:16');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Gegevens worden geëxporteerd voor tabel `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `name`, `content`, `created_at`) VALUES
(1, 1, 'demo', 'This site is really helping me keep track of all my manga!', '2025-05-18 00:21:55'),
(2, NULL, 'Alice', 'Great idea for a website. I always lose track of where I left off.', '2025-05-16 00:21:55'),
(3, NULL, 'Bob', 'Any recommendations for new manga to read?', '2025-05-14 00:21:55'),
(4, 3, 'ZiggyZiggy', 'gf', '2025-05-18 00:29:39'),
(5, 3, 'ZiggyZiggy', 'ZiggyZiggy', '2025-05-18 10:55:38');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `manga_chapters`
--

CREATE TABLE `manga_chapters` (
  `id` int NOT NULL,
  `bookmark_id` int NOT NULL,
  `user_id` int NOT NULL,
  `chapter_number` varchar(20) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Gegevens worden geëxporteerd voor tabel `manga_chapters`
--

INSERT INTO `manga_chapters` (`id`, `bookmark_id`, `user_id`, `chapter_number`, `is_read`, `read_date`, `created_at`) VALUES
(42, 23, 4, '1', 1, '2025-05-18 08:44:52', '2025-05-18 08:44:52'),
(43, 23, 4, '2', 1, '2025-05-18 08:44:54', '2025-05-18 08:44:54'),
(44, 23, 4, '4', 1, '2025-05-18 08:44:55', '2025-05-18 08:44:55'),
(45, 23, 4, '3', 1, '2025-05-18 08:44:55', '2025-05-18 08:44:55'),
(46, 23, 4, '5', 1, '2025-05-18 08:44:59', '2025-05-18 08:44:56'),
(47, 23, 4, '6', 1, '2025-05-18 08:44:56', '2025-05-18 08:44:56');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `reads_log`
--

CREATE TABLE `reads_log` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `manga_title` varchar(255) NOT NULL,
  `chapter` varchar(50) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Gegevens worden geëxporteerd voor tabel `reads_log`
--

INSERT INTO `reads_log` (`id`, `user_id`, `manga_title`, `chapter`, `read_at`) VALUES
(1, 3, 'Ziggy', 'Ziggy', '2025-05-18 00:29:26'),
(2, 3, 'One Piece', '1', '2025-05-18 01:46:48'),
(3, 3, 'One Piece', '1', '2025-05-18 01:47:06'),
(4, 3, 'Naruto', '1', '2025-05-18 01:47:47'),
(5, 3, 'Naruto', '1', '2025-05-18 01:48:01'),
(6, 3, 'Naruto', '4', '2025-05-18 01:48:15'),
(7, 3, 'Naruto', '5', '2025-05-18 01:49:49'),
(8, 3, 'Naruto', '5', '2025-05-18 01:50:05'),
(9, 3, 'SD Gundam Sangokuden Brave Battle Warriors: Souseiki', '10', '2025-05-18 02:12:26'),
(10, 3, 'SD Gundam Sangokuden Brave Battle Warriors: Souseiki', '11', '2025-05-18 02:12:39'),
(11, 3, 'SD Gundam Sangokuden Brave Battle Warriors: Souseiki', '12', '2025-05-18 02:12:41'),
(12, 3, 'C!!', '0', '2025-05-18 02:13:52'),
(13, 3, 'C!!', '2', '2025-05-18 02:13:58'),
(14, 3, 'C!!', '3', '2025-05-18 02:14:01'),
(15, 3, 'C!!', '4', '2025-05-18 02:14:01'),
(16, 3, 'C!!', '5', '2025-05-18 02:14:01'),
(17, 3, 'C!!', '6', '2025-05-18 02:14:02'),
(18, 3, 'C!!', '7', '2025-05-18 02:14:02'),
(19, 3, 'C!!', '8', '2025-05-18 02:14:03'),
(20, 3, 'C!!', '9', '2025-05-18 02:14:03'),
(21, 3, 'C!!', '10', '2025-05-18 02:14:05'),
(22, 3, 'C!!', '11', '2025-05-18 02:14:05'),
(23, 3, 'C!!', '12', '2025-05-18 02:14:05'),
(24, 3, 'C!!', '13', '2025-05-18 02:14:06'),
(25, 3, 'C!!', '14', '2025-05-18 02:14:06'),
(26, 3, 'C!!', '15', '2025-05-18 02:22:02'),
(27, 3, 'C!!', '16', '2025-05-18 02:22:02'),
(28, 3, 'C!!', '17', '2025-05-18 02:22:02'),
(29, 3, 'C!!', '26', '2025-05-18 02:22:03'),
(30, 3, 'C!!', '27', '2025-05-18 02:22:06'),
(31, 3, 'bhn', 'bn', '2025-05-18 02:31:08'),
(32, 4, 'Naruto', '5', '2025-05-18 08:44:13'),
(33, 3, 'f', '4', '2025-05-18 09:12:42'),
(34, 3, 'f', '4', '2025-05-18 09:13:53'),
(35, 3, 'sd', 'sd', '2025-05-18 09:14:16'),
(36, 3, 'g', 'g', '2025-05-18 09:24:52'),
(37, 3, 'Romeo', '1', '2025-05-18 11:12:22'),
(38, 3, 'Deathtament: Shin Megami Tensei DSJ Another Report', '17', '2025-05-18 11:12:29'),
(39, 6, 'PAINS', '6', '2025-05-18 11:41:38'),
(40, 6, 'lol', '5', '2025-05-18 11:41:53'),
(41, 6, 'ghdfg', '50', '2025-05-18 11:42:31'),
(42, 6, 'Naruto', '5', '2025-05-18 11:42:53'),
(43, 6, 'Lol', '5', '2025-05-18 11:54:16');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Gegevens worden geëxporteerd voor tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `created_at`) VALUES
(1, 'demo', '$2y$10$AuhhokLyQtGGXB.GfeYVjOXdFCJcH9eAjpXQYhQdE1OklH.rvGyNW', NULL, '2025-05-18 00:21:55'),
(2, 'Ziggy', '$2y$10$XDHBtbv/dljb.ngfcN08TOaadJQOPWk39yqYSz87pDA0ghGofXT4q', '', '2025-05-18 00:29:05'),
(3, 'ZiggyZiggy', '$2y$10$uSneZr1g.7N.IwqJQHZy4OUPOZxqbQNzOt0dmnwVhDMsMtB0zVRn2', '', '2025-05-18 00:29:20'),
(4, 'Naomi', '$2y$10$cJ9aJfl34cdyJ8vrZ3cpJe/Rpa4K/rmKfYUqeso3LCkt8Mh91AFSq', '', '2025-05-18 08:43:57'),
(5, 'ZiggyZiggyZiggyZiggy', '$2y$10$R3sTSRrMi1Nrgkwp1lYeDe/OGjNMv0ciMdRWwmaaFlWpAznktgAOy', '', '2025-05-18 09:43:21'),
(6, 'PAINSPAINS', '$2y$10$Er5VXDXZEejOYMWu2WUheuyd7qlp4bWzUMkKnR4ia/frlQ0pkp62K', '', '2025-05-18 11:41:27');

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_manga` (`user_id`,`manga_title`),
  ADD KEY `idx_bookmarks_api` (`api_id`,`api_source`);

--
-- Indexen voor tabel `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexen voor tabel `manga_chapters`
--
ALTER TABLE `manga_chapters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_chapter` (`bookmark_id`,`chapter_number`),
  ADD KEY `idx_user_chapters` (`user_id`,`bookmark_id`);

--
-- Indexen voor tabel `reads_log`
--
ALTER TABLE `reads_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexen voor tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT voor een tabel `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT voor een tabel `manga_chapters`
--
ALTER TABLE `manga_chapters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT voor een tabel `reads_log`
--
ALTER TABLE `reads_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT voor een tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Beperkingen voor tabel `manga_chapters`
--
ALTER TABLE `manga_chapters`
  ADD CONSTRAINT `manga_chapters_ibfk_1` FOREIGN KEY (`bookmark_id`) REFERENCES `bookmarks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `manga_chapters_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `reads_log`
--
ALTER TABLE `reads_log`
  ADD CONSTRAINT `reads_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
