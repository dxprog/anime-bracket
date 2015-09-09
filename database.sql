-- phpMyAdmin SQL Dump
-- version 3.4.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 09, 2015 at 04:57 AM
-- Server version: 1.0.17
-- PHP Version: 5.6.8-1~dotdeb+wheezy.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `anime_bracket`
--


-- --------------------------------------------------------

--
-- Table structure for table `bot_users`
--

DROP TABLE IF EXISTS `bot_users`;
CREATE TABLE IF NOT EXISTS `bot_users` (
  `bot_id` int(10) NOT NULL AUTO_INCREMENT,
  `bot_name` varchar(50) NOT NULL,
  `bot_password` varchar(50) NOT NULL,
  `bot_hash` varchar(255) DEFAULT NULL,
  `bot_cookie` varchar(255) DEFAULT NULL,
  `bot_data` text,
  `bot_callback` varchar(30) NOT NULL,
  `bot_updated` int(10) NOT NULL,
  `bot_created` int(10) NOT NULL,
  `bot_enabled` tinyint(4) NOT NULL,
  PRIMARY KEY (`bot_id`),
  UNIQUE KEY `bot_name` (`bot_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bracket`
--

DROP TABLE IF EXISTS `bracket`;
CREATE TABLE IF NOT EXISTS `bracket` (
  `bracket_id` int(11) NOT NULL AUTO_INCREMENT,
  `bracket_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `bracket_perma` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `bracket_start` int(11) NOT NULL,
  `bracket_state` int(11) NOT NULL,
  `bracket_pic` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `winner_character_id` int(11) DEFAULT NULL,
  `bracket_rules` text COLLATE utf8_unicode_ci NOT NULL,
  `bracket_source` int(11) NOT NULL,
  `bracket_advance_hour` int(11) DEFAULT NULL,
  `bracket_name_label` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bracket_source_label` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bracket_score` decimal(10,0) DEFAULT NULL,
  `bracket_external_id` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bracket_min_age` int(11) DEFAULT NULL,
  `bracket_hidden` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`bracket_id`),
  UNIQUE KEY `U_bracket_perma` (`bracket_perma`),
  KEY `FK_winner_character_id` (`winner_character_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bracket_owners`
--

DROP TABLE IF EXISTS `bracket_owners`;
CREATE TABLE IF NOT EXISTS `bracket_owners` (
  `bracket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  KEY `FK_bracket_owners_user_id` (`user_id`),
  KEY `FK_bracket_owners_bracket_id` (`bracket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `character`
--

DROP TABLE IF EXISTS `character`;
CREATE TABLE IF NOT EXISTS `character` (
  `character_id` int(11) NOT NULL AUTO_INCREMENT,
  `bracket_id` int(11) NOT NULL,
  `character_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `character_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `character_seed` int(11) DEFAULT NULL,
  PRIMARY KEY (`character_id`),
  KEY `FK_character_bracket_id` (`bracket_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mal_items`
--

DROP TABLE IF EXISTS `mal_items`;
CREATE TABLE IF NOT EXISTS `mal_items` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `item_pic` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `item_perma` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `item_type` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `item_perma` (`item_perma`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mal_xref`
--

DROP TABLE IF EXISTS `mal_xref`;
CREATE TABLE IF NOT EXISTS `mal_xref` (
  `mal_parent` int(11) NOT NULL,
  `mal_child` int(11) NOT NULL,
  KEY `FK_mal_xref_mal_parent` (`mal_parent`),
  KEY `FK_mal_xref_mal_child` (`mal_child`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nominee`
--

DROP TABLE IF EXISTS `nominee`;
CREATE TABLE IF NOT EXISTS `nominee` (
  `nominee_id` int(11) NOT NULL AUTO_INCREMENT,
  `bracket_id` int(11) NOT NULL,
  `nominee_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nominee_source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nominee_created` int(11) NOT NULL,
  `nominee_processed` bit(1) DEFAULT NULL,
  `nominee_image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`nominee_id`),
  KEY `FK_nominee_bracket_id` (`bracket_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `round`
--

DROP TABLE IF EXISTS `round`;
CREATE TABLE IF NOT EXISTS `round` (
  `round_id` int(11) NOT NULL AUTO_INCREMENT,
  `bracket_id` int(11) NOT NULL,
  `round_tier` int(11) NOT NULL,
  `round_order` int(11) NOT NULL,
  `round_group` int(11) NOT NULL,
  `round_character1_id` int(11) NOT NULL,
  `round_character2_id` int(11) NOT NULL,
  `round_final` bit(1) NOT NULL,
  `round_character1_votes` int(11) DEFAULT NULL,
  `round_character2_votes` int(11) DEFAULT NULL,
  PRIMARY KEY (`round_id`),
  KEY `FK_round_bracket_id` (`bracket_id`),
  KEY `FK_round_character1_id` (`round_character1_id`),
  KEY `FK_round_character2_id` (`round_character2_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `user_admin` tinyint(1) NOT NULL,
  `user_ip` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `user_age` int(11) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
CREATE TABLE IF NOT EXISTS `votes` (
  `vote_date` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  `character_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bracket_id` int(11) NOT NULL,
  UNIQUE KEY `uc_user_id_round_id` (`user_id`,`round_id`),
  KEY `FK_vote_round_id` (`round_id`),
  KEY `FK_vote_character_id` (`character_id`),
  KEY `FK_votes_bracket_id` (`bracket_id`),
  KEY `FK_votes_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bracket`
--
ALTER TABLE `bracket`
  ADD CONSTRAINT `FK_winner_character_id` FOREIGN KEY (`winner_character_id`) REFERENCES `character` (`character_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bracket_owners`
--
ALTER TABLE `bracket_owners`
  ADD CONSTRAINT `FK_bracket_owners_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_bracket_owners_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `character`
--
ALTER TABLE `character`
  ADD CONSTRAINT `FK_character_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mal_xref`
--
ALTER TABLE `mal_xref`
  ADD CONSTRAINT `FK_mal_xref_mal_child` FOREIGN KEY (`mal_child`) REFERENCES `mal_items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_mal_xref_mal_parent` FOREIGN KEY (`mal_parent`) REFERENCES `mal_items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `nominee`
--
ALTER TABLE `nominee`
  ADD CONSTRAINT `FK_nominee_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `round`
--
ALTER TABLE `round`
  ADD CONSTRAINT `FK_round_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_round_character1_id` FOREIGN KEY (`round_character1_id`) REFERENCES `character` (`character_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_round_character2_id` FOREIGN KEY (`round_character2_id`) REFERENCES `character` (`character_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `FK_vote_character_id` FOREIGN KEY (`character_id`) REFERENCES `character` (`character_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_vote_round_id` FOREIGN KEY (`round_id`) REFERENCES `round` (`round_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_votes_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_votes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
