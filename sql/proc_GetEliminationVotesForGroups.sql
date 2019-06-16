/**
 * Returns information about tiers and groups for a bracket
 */
DROP PROCEDURE IF EXISTS `proc_GetEliminationVotesForGroups`;

DELIMITER //

CREATE PROCEDURE `proc_GetEliminationVotesForGroups` (
  bracketId INT,
  maxDate INT
)
BEGIN

  SELECT
    COUNT(DISTINCT v.user_id) AS total,
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

END //
