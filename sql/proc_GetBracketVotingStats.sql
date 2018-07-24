/**
 * Return vote, user voting stats for a bracket
 */
DROP PROCEDURE IF EXISTS `proc_GetBracketVotingStats`;

DELIMITER //

CREATE PROCEDURE `proc_GetBracketVotingStats` (
  bracketId INT
)
BEGIN

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

END //