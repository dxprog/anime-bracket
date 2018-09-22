-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 22, 2018 at 09:19 PM
-- Server version: 10.0.17-MariaDB-1~wheezy-log
-- PHP Version: 7.0.31-1~dotdeb+8.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `anime_bracket`
--
CREATE DATABASE IF NOT EXISTS `anime_bracket` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `anime_bracket`;

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `proc_AddUserToBracket`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_AddUserToBracket` (`bracketId` INT, `userId` INT)  BEGIN

  INSERT INTO
    `bracket_owners`
  VALUES (
    bracketId,
    userId
  );

END$$

DROP PROCEDURE IF EXISTS `proc_AdvanceEliminationRound`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_AdvanceEliminationRound` (`bracketId` INT)  BEGIN

  DECLARE currentGroup INT;

  SELECT
    MIN(`round_group`) INTO currentGroup
  FROM
    `round`
  WHERE
    `bracket_id` = bracketId AND
    `round_final` != 1 AND
    `round_deleted` = 0;


  UPDATE
    `round`
  SET
    `round_final` = 1,
    `round_end_date` = UNIX_TIMESTAMP(NOW())
  WHERE
    `bracket_id` = bracketId AND
    `round_tier` = 0 AND
    `round_group` = currentGroup;

END$$

DROP PROCEDURE IF EXISTS `proc_CleanBrackets`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_CleanBrackets` ()  BEGIN

    /* Brackets in nomination phase will be reverted to "not started" after 30 days */
    UPDATE
        `bracket`
    SET
        `bracket_state` = 0
    WHERE
        `bracket_state` = 1
        AND `bracket_start` < (UNIX_TIMESTAMP() - 86400 * 30);

END$$

DROP PROCEDURE IF EXISTS `proc_GetBracketActiveGroupTier`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetBracketActiveGroupTier` (`bracketId` INT)  BEGIN

  SELECT
    `round_tier`,
    `round_group`
  FROM
    `round`
  WHERE
    `bracket_id` = bracketId AND
    `round_deleted` = 0 AND
    `round_final` = 0
  ORDER BY
    `round_tier` ASC,
    `round_group` ASC;

END$$

DROP PROCEDURE IF EXISTS `proc_GetBracketFinalScore`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetBracketFinalScore` (`bracketId` INT)  BEGIN

  SELECT
    COUNT(1) / (
      SELECT
        COUNT(1)
      FROM
        `round`
      WHERE
        `bracket_id` = bracketId
        AND `round_tier` > 0
        AND `round_deleted` = 0
    ) AS total
  FROM
    `votes`
  WHERE
    `bracket_id` = bracketId
    AND `round_id` NOT IN (
      SELECT
        `round_id`
      FROM
        `round`
      WHERE
        `bracket_id` = bracketId
        AND `round_tier` = 0
        AND `round_deleted` = 0
    );

END$$

DROP PROCEDURE IF EXISTS `proc_GetBracketRoundInfo`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetBracketRoundInfo` (`bracketId` INT)  BEGIN

  SELECT
    SUM(
      CASE WHEN `round_tier` = 1 THEN 1 ELSE 0 END
    ) AS total,
    MAX(`round_tier`) AS max_tier,
    MAX(`round_group`) AS max_group
  FROM
    `round`
  WHERE
    `bracket_id` = bracketId AND
    `round_tier` > 0 AND
    `round_deleted` = 0;

END$$

DROP PROCEDURE IF EXISTS `proc_GetBracketRounds`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetBracketRounds` (`bracketId` INT, `tierNum` INT, `groupNum` INT, `userId` INT)  BEGIN

  SELECT
    *,
    (
      SELECT
        `character_id`
      FROM
        `votes`
      WHERE
        `user_id` = userId AND
        `round_id` = r.`round_id`
    ) AS user_vote
  FROM
    `round` r
  WHERE
    r.`round_deleted` = 0 AND
    r.`bracket_id` = bracketId AND
    (
      tierNum IS NULL OR
      r.`round_tier` = tierNum
    ) AND
    (
      groupNum IS NULL OR
      r.`round_group` = groupNum
    )
  ORDER BY
    r.`round_order`;

END$$

DROP PROCEDURE IF EXISTS `proc_GetBracketUsers`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetBracketUsers` (`bracketId` INT)  BEGIN

  SELECT
    u.*
  FROM
    `bracket_owners` bo
  INNER JOIN
    `users` u ON u.`user_id` = bo.`user_id`
  WHERE
    bo.`bracket_id` = bracketId;

END$$

DROP PROCEDURE IF EXISTS `proc_GetBracketVotesForUser`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetBracketVotesForUser` (`bracketId` INT, `userId` INT)  BEGIN

  SELECT
    `round_id`,
    `character_id`
  FROM
    `votes`
  WHERE
    `user_id` = userId
    AND `bracket_id` = bracketId;

END$$

DROP PROCEDURE IF EXISTS `proc_GetBracketVotingStats`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetBracketVotingStats` (`bracketId` INT)  BEGIN

  SELECT
    COUNT(1) AS total,
    COUNT(DISTINCT v.user_id) AS user_total,
    r.`round_tier`,
    r.`round_group`
  FROM
    `votes` v
  INNER JOIN
    `round` r
  ON
    r.`round_id` = v.`round_id`
  WHERE
    v.`bracket_id` = bracketId
  GROUP BY
    r.`round_tier`,
    r.`round_group`;

END$$

DROP PROCEDURE IF EXISTS `proc_GetCharacterVotesForRound`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetCharacterVotesForRound` (`roundId` INT)  BEGIN

  SELECT
    COUNT(1) AS total,
    `character_id`
  FROM
    `votes`
  WHERE
    `round_id` = roundId
  GROUP BY
    `character_id`;

END$$

DROP PROCEDURE IF EXISTS `proc_GetEliminationGroupsCharacterVotes`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetEliminationGroupsCharacterVotes` (`bracketId` INT, `maxDate` INT)  BEGIN

  SELECT
    COUNT(1) AS total,
    r.`round_group`,
    c.*
  FROM
    `round` r
  INNER JOIN
    `character` c ON c.`character_id` = r.`round_character1_id`
  LEFT OUTER JOIN
    `votes` v ON v.`character_id` = r.`round_character1_id`
  WHERE
    v.`bracket_id` = bracketId
    AND v.`vote_date` <= maxDate
    AND r.`round_tier` = 0
    AND r.`round_deleted` = 0
  GROUP BY
    c.`character_id`;

END$$

DROP PROCEDURE IF EXISTS `proc_GetEliminationVotesForGroups`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetEliminationVotesForGroups` (`bracketId` INT, `maxDate` INT)  BEGIN

  SELECT
    COUNT(1) AS total,
    r.`round_group`
  FROM
    `votes` v
  INNER JOIN
    `round` r ON r.`round_id` = v.`round_id`
  WHERE
    v.`bracket_id` = bracketId
    AND v.`vote_date` <= maxDate
    AND r.`round_tier` = 0
    AND r.`round_deleted` = 0
  GROUP BY
    r.`round_group`;

END$$

DROP PROCEDURE IF EXISTS `proc_GetUserBrackets`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetUserBrackets` (`source` INT, `userId` INT)  BEGIN

  SELECT
    *
  FROM
    `bracket`
  WHERE
    `bracket_source` = source AND
    `bracket_id` IN (
      SELECT
        `bracket_id`
      FROM
        `bracket_owners`
      WHERE
        `user_id` = userId
    );

END$$

DROP PROCEDURE IF EXISTS `proc_RemoveUserFromBracket`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_RemoveUserFromBracket` (`bracketId` INT, `userId` INT)  BEGIN

  DELETE FROM
    `bracket_owners`
  WHERE
    `bracket_id` = bracketId
    AND `user_id` = userId;

END$$

DROP PROCEDURE IF EXISTS `proc_GetNextRounds`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetNextRounds` (`bracketId` INT)  BEGIN

    DECLARE currentTier INT;
    DECLARE currentGroup INT;
    DECLARE lastRoundInCurrentGroup INT;

    SELECT
        `round_tier`, `round_group` INTO currentTier, currentGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_final` = 0
        AND `round_deleted` = 0
    ORDER BY
        `round_tier` ASC,
        `round_group` ASC;

    SELECT
        MAX(round_id) INTO lastRoundInCurrentGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_tier` = currentTier
        AND `round_group` = currentGroup
        AND `round_final` = 0
        AND `round_deleted` = 0;

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
        AND `round_deleted` = 0
    GROUP BY
        `round_group`,
        `round_tier`
    ORDER BY
        nextTier ASC,
        calculatedGroup ASC
    LIMIT 1;

END$$

DROP PROCEDURE IF EXISTS `proc_GetRoundWinner`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetRoundWinner` (`roundId` INT)  BEGIN

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

DROP PROCEDURE IF EXISTS `proc_RollbackBracket`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_RollbackBracket` (`bracketId` INT, `roundTier` INT, `roundGroup` INT)  BEGIN

  /* Delete rounds in the next tier after (and including) the group to roll back to and all tiers after that */
  UPDATE
    `round`
  SET
    `round_deleted` = 1
  WHERE
    `bracket_id` = bracketId
    AND (
      (
        `round_tier` = roundTier + 1
        AND `round_group` >= roundGroup
      )
      OR `round_tier` > roundTier + 1
    );

  /* Set the new tier/group and all groups after as non-finalized and clear any calculated vote counts */
  UPDATE
    `round`
  SET
    `round_final` = 0,
    `round_character1_votes` = NULL,
    `round_character2_votes` = NULL,
    `round_end_date` = NULL
  WHERE
    `bracket_id` = bracketId
    AND `round_deleted` = 0
    AND (
      (
        `round_tier` = roundTier
        AND `round_group` >= roundGroup
      )
      OR `round_tier` > roundTier
    );

  /* Finally, delete all votes from the non-finalized rounds */
  DELETE FROM
    `votes`
  WHERE
    `round_id` IN (
      SELECT
        `round_id`
      FROM
        `round`
      WHERE
        `bracket_id` = bracketId
        AND `round_final` = 0
        AND `round_deleted` = 0
    );

  /* If rolling back to an eliminations round, kill any actual bracket setup */
  IF roundTier = 0 THEN
    BEGIN
      UPDATE
        `round`
      SET
        `round_deleted` = 1
      WHERE
        `bracket_id` = bracketId
        AND `round_tier` > 0;
    END;
  END IF;

END$$

DROP PROCEDURE IF EXISTS `proc_UpdateBracketScores`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_UpdateBracketScores` ()  BEGIN

    DECLARE bracketId INT;
    DECLARE score DECIMAL;

    DECLARE cursorDone BOOLEAN DEFAULT FALSE;
    DECLARE query CURSOR FOR

        SELECT
            v.`bracket_id`,
            (LOG10(COUNT(1)) + (AVG(v.`vote_date`) - (UNIX_TIMESTAMP() - 2592000)) / 45000) * b.bracket_state AS score
        FROM
            `votes` v
        INNER JOIN
            `bracket` b ON b.`bracket_id` = v.`bracket_id`
        WHERE
            v.`vote_date` >= (UNIX_TIMESTAMP() - 2592000)
            AND b.`bracket_state` IN (2, 3)
        GROUP BY
            v.`bracket_id`

        UNION

        SELECT
            n.`bracket_id`,
            LOG10(COUNT(1)) + (b.bracket_start - (UNIX_TIMESTAMP() - 2592000)) / 45000 AS score
        FROM
            `nominee` n
        INNER JOIN
            `bracket` b ON b.`bracket_id` = n.`bracket_id`
        WHERE
            b.`bracket_start` >= (UNIX_TIMESTAMP() - 2592000)
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `bracket_blurb` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bracket_captcha` int(11) NOT NULL,
  PRIMARY KEY (`bracket_id`),
  UNIQUE KEY `U_bracket_perma` (`bracket_perma`),
  KEY `FK_winner_character_id` (`winner_character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`category_id`)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logins`
--

DROP TABLE IF EXISTS `logins`;
CREATE TABLE IF NOT EXISTS `logins` (
  `user_id` int(11) NOT NULL,
  `login_date` int(11) NOT NULL,
  `login_ip` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  KEY `user_id` (`user_id`,`login_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
  `round_final` tinyint(1) NOT NULL,
  `round_character1_votes` int(11) DEFAULT NULL,
  `round_character2_votes` int(11) DEFAULT NULL,
  `round_end_date` int(11) DEFAULT NULL,
  `round_deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`round_id`),
  KEY `FK_round_bracket_id` (`bracket_id`),
  KEY `FK_round_character1_id` (`round_character1_id`),
  KEY `FK_round_character2_id` (`round_character2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `typeahead`
--

DROP TABLE IF EXISTS `typeahead`;
CREATE TABLE IF NOT EXISTS `typeahead` (
  `typeahead_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `typeahead_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `typeahead_category` int(11) NOT NULL,
  `typeahead_image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`typeahead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
  ADD CONSTRAINT `FK_winner_character_id` FOREIGN KEY (`winner_character_id`) REFERENCES `character` (`character_id`) ON DELETE CASCADE;

--
-- Constraints for table `bracket_owners`
--
ALTER TABLE `bracket_owners`
  ADD CONSTRAINT `FK_bracket_owners_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_bracket_owners_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `character`
--
ALTER TABLE `character`
  ADD CONSTRAINT `FK_character_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE;

--
-- Constraints for table `logins`
--
ALTER TABLE `logins`
  ADD CONSTRAINT `FK_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `mal_xref`
--
ALTER TABLE `mal_xref`
  ADD CONSTRAINT `FK_mal_xref_mal_child` FOREIGN KEY (`mal_child`) REFERENCES `mal_items` (`item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_mal_xref_mal_parent` FOREIGN KEY (`mal_parent`) REFERENCES `mal_items` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `nominee`
--
ALTER TABLE `nominee`
  ADD CONSTRAINT `FK_nominee_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE;

--
-- Constraints for table `round`
--
ALTER TABLE `round`
  ADD CONSTRAINT `FK_round_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_round_character1_id` FOREIGN KEY (`round_character1_id`) REFERENCES `character` (`character_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_round_character2_id` FOREIGN KEY (`round_character2_id`) REFERENCES `character` (`character_id`) ON DELETE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `FK_vote_character_id` FOREIGN KEY (`character_id`) REFERENCES `character` (`character_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_vote_round_id` FOREIGN KEY (`round_id`) REFERENCES `round` (`round_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_votes_bracket_id` FOREIGN KEY (`bracket_id`) REFERENCES `bracket` (`bracket_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_votes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;
