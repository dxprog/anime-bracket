/**
 * Returns information about tiers and groups for a bracket
 */
DROP PROCEDURE IF EXISTS `proc_AdvanceEliminationRound`;

DELIMITER //

CREATE PROCEDURE `proc_AdvanceEliminationRound` (bracketId INT)
BEGIN

  DECLARE currentGroup INT;

  SELECT
    MIN(`round_group`) INTO currentGroup
  FROM
    `round`
  WHERE
    `bracket_id` = bracketId AND
    `round_final` != 1;

  UPDATE
    `round`
  SET
    `round_final` = 1,
    `round_end_date` = UNIX_TIMESTAMP(NOW())
  WHERE
    `bracket_id` = bracketId AND
    `round_tier` = 0 AND
    `round_group` = currentGroup;

END //
