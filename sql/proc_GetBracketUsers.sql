/**
 * Returns all users that are assigned to a bracket
 */
DROP PROCEDURE IF EXISTS `proc_GetBracketUsers`;

DELIMITER //

CREATE PROCEDURE `proc_GetBracketUsers` (
  bracketId INT
)
BEGIN

  SELECT
    u.*
  FROM
    `bracket_owners` bo
  INNER JOIN
    `users` u ON u.`user_id` = bo.`user_id`
  WHERE
    bo.`bracket_id` = bracketId;

END //
