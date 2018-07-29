/**
 * Adds a user to a bracket
 */
DROP PROCEDURE IF EXISTS `proc_AddUserToBracket`;

DELIMITER //

CREATE PROCEDURE `proc_AddUserToBracket` (
  bracketId INT,
  userId INT
)
BEGIN

  INSERT INTO
    `bracket_owners`
  VALUES (
    bracketId,
    userId
  );

END //
