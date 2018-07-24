/**
 * Returns all of a user's votes for a bracket
 */
DROP PROCEDURE IF EXISTS `proc_GetBracketVotesForUser`;

DELIMITER //

CREATE PROCEDURE `proc_GetBracketVotesForUser` (
  bracketId INT,
  userId INT
)
BEGIN

  SELECT
    `round_id`,
    `character_id`
  FROM
    `votes`
  WHERE
    `user_id` = userId
    AND `bracket_id` = bracketId;

END //