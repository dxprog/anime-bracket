/**
 * Returns tier and group of the first unfinished round in a bracket
 */
DROP PROCEDURE IF EXISTS `proc_GetBracketActiveGroupTier`;

DELIMITER //

CREATE PROCEDURE `proc_GetBracketActiveGroupTier` (
  bracketId INT
)
BEGIN

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

END //