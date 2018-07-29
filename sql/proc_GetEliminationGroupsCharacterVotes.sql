/**
 * Returns information about tiers and groups for a bracket
 */
DROP PROCEDURE IF EXISTS `proc_GetEliminationGroupsCharacterVotes`;

DELIMITER //

CREATE PROCEDURE `proc_GetEliminationGroupsCharacterVotes` (
  bracketId INT,
  maxDate INT
)
BEGIN

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

END //