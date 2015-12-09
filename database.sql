-- phpMyAdmin SQL Dump
-- version 3.5.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 10, 2015 at 04:32 AM
-- Server version: 5.5.46-MariaDB-1~wheezy
-- PHP Version: 5.6.16-1~dotdeb+7.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `anime_bracket`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `proc_CleanBrackets`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_CleanBrackets`()
BEGIN

    
    UPDATE
        `bracket`
    SET
        `bracket_state` = 0
    WHERE
        `bracket_state` = 1
        AND `bracket_start` < (UNIX_TIMESTAMP() - 86400 * 30);

END$$

DROP PROCEDURE IF EXISTS `proc_GetNextRounds`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetNextRounds`(bracketId INT)
BEGIN

    DECLARE currentTier INT;
    DECLARE currentGroup INT;
    DECLARE lastRoundInCurrentGroup INT;

    SELECT
        MIN(round_tier) INTO currentTier
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_final` = 0;

    SELECT
        MIN(round_group) INTO currentGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_tier` = currentTier
        AND `round_final` = 0;

    SELECT
        MAX(round_id) INTO lastRoundInCurrentGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_tier` = currentTier
        AND `round_group` = currentGroup
        AND `round_final` = 0;

    SELECT
        MIN(`round_tier`) AS nextTier,
        (MIN(`round_tier`) * MAX(`round_group`) + MIN(`round_group`)) AS calculatedGroup,
        `round_group` AS nextGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_final` = 0
        AND `round_id` > lastRoundInCurrentGroup
    GROUP BY
        `round_group`,
        `round_tier`
    ORDER BY
        nextTier ASC,
        calculatedGroup ASC
    LIMIT 1;

END$$

DROP PROCEDURE IF EXISTS `proc_GetRoundWinner`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetRoundWinner`(roundId INT)
BEGIN

    DECLARE character1Id INT;
    DECLARE character2Id INT;
    DECLARE character1Votes INT;
    DECLARE character2Votes INT;
    DECLARE character1Seed INT;
    DECLARE character2Seed INT;

    SELECT
        `round_character1_id`, `round_character2_id` INTO character1Id, character2Id
    FROM
        `round`
    WHERE
        `round_id` = roundId;

    SELECT
        COUNT(1) INTO character1Votes
    FROM
        `votes`
    WHERE
        `round_id` = roundId
        AND `character_id` = character1Id;

    SELECT
        COUNT(1) INTO character2Votes
    FROM
        `votes`
    WHERE
        `round_id` = roundId
        AND `character_id` = character2Id;

    CASE
        WHEN character1Votes > character2Votes THEN SELECT character1Id AS character_id;
        WHEN character2Votes > character1Votes THEN SELECT character2Id AS character_id;
        ELSE
            BEGIN

                SELECT
                    `character_seed` INTO character1Seed
                FROM
                    `character`
                WHERE
                    `character_id` = character1Id;

                SELECT
                    `character_seed` INTO character2Seed
                FROM
                    `character`
                WHERE
                    `character_id` = character2Id;

                IF character1Seed < character2Seed THEN
                    SELECT character1Id AS character_id;
                ELSE
                    SELECT character2Id AS character_id;
                END IF;

            END;
    END CASE;

END$$

DROP PROCEDURE IF EXISTS `proc_UpdateBracketScores`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_UpdateBracketScores`()
BEGIN

    DECLARE bracketId INT;
    DECLARE score DECIMAL;

    DECLARE cursorDone BOOLEAN DEFAULT FALSE;
    DECLARE query CURSOR FOR

        SELECT
            v.`bracket_id`,
            (LOG10(COUNT(1)) + (AVG(v.`vote_date`) - (UNIX_TIMESTAMP() - 86400)) / 45000) * b.bracket_state AS score
        FROM
            `votes` v
        INNER JOIN
            `bracket` b ON b.`bracket_id` = v.`bracket_id`
        WHERE
            v.`vote_date` >= (UNIX_TIMESTAMP() - 86400)
            AND b.`bracket_state` IN (2, 3)
        GROUP BY
            v.`bracket_id`

        UNION

        SELECT
            n.`bracket_id`,
            LOG10(COUNT(1)) + (b.bracket_start - (UNIX_TIMESTAMP() - 86400)) / 45000 AS score
        FROM
            `nominee` n
        INNER JOIN
            `bracket` b ON b.`bracket_id` = n.`bracket_id`
        WHERE
            b.`bracket_start` >= (UNIX_TIMESTAMP() - 86400)
            AND b.`bracket_state` = 1
        GROUP BY
            n.`bracket_id`;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET cursorDone = TRUE;

    OPEN query;
    WHILE cursorDone = FALSE DO

        FETCH query INTO bracketId, score;

        UPDATE
            `bracket`
        SET
            `bracket_score` = score
        WHERE
            `bracket_id` = bracketId;

    END WHILE;

END$$

DELIMITER ;

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
  `bracket_min_age` int(11) DEFAULT '2592000',
  `bracket_hidden` tinyint(4) DEFAULT '1',
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
  `user_age` int(11) DEFAULT NULL,
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
  ADD CONSTRAINT `FK_votes_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_votes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_vote_character_id` FOREIGN KEY (`character_id`) REFERENCES `character` (`character_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_vote_round_id` FOREIGN KEY (`round_id`) REFERENCES `round` (`round_id`) ON DELETE CASCADE ON UPDATE CASCADE;
