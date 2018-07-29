/**
 * Removes a user from a bracket
 */
DROP PROCEDURE IF EXISTS `proc_RemoveUserFromBracket`;

DELIMITER //

CREATE PROCEDURE `proc_RemoveUserFromBracket` (
  bracketId INT,
  userId INT
)
BEGIN

  DELETE FROM
    `bracket_owners`
  WHERE
    `bracket_id` = bracketId
    AND `user_id` = userId;

END //
