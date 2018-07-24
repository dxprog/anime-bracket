/**
 * Returns the number of votes for all characters in a round
 */
DROP PROCEDURE IF EXISTS `proc_GetCharacterVotesForRound`;

DELIMITER //

CREATE PROCEDURE `proc_GetCharacterVotesForRound` (
  roundId INT
)
BEGIN

  SELECT
    COUNT(1) AS total,
    `character_id`
  FROM
    `votes`
  WHERE
    `round_id` = roundId
  GROUP BY
    `character_id`;

END //
