/**
 * Returns rounds and user voting data for a bracket by tier and group, if passed
 */
DROP PROCEDURE IF EXISTS `proc_GetBracketRounds`;

DELIMITER //

CREATE PROCEDURE `proc_GetBracketRounds` (
  bracketId INT,
  tierNum INT,
  groupNum INT,
  userId INT
)
BEGIN

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

END //