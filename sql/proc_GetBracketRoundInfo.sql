/**
 * Returns information about tiers and groups for a bracket
 */
DROP PROCEDURE IF EXISTS `proc_GetBracketRoundInfo`;

DELIMITER //

CREATE PROCEDURE `proc_GetBracketRoundInfo` (bracketId INT)
BEGIN

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
    `round_tier` > 0;

END //