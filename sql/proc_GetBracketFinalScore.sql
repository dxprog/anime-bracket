/**
 * Returns the number of votes for all characters in a round
 */
DROP PROCEDURE IF EXISTS `proc_GetBracketFinalScore`;

DELIMITER //

CREATE PROCEDURE `proc_GetBracketFinalScore` (
  bracketId INT
)
BEGIN

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

END //
