/**
 * Returns information about tiers and groups for a bracket
 */
DROP PROCEDURE IF EXISTS `proc_GetUserBrackets`;

DELIMITER //

CREATE PROCEDURE `proc_GetUserBrackets` (source INT, userId INT)
BEGIN

  SELECT
    *
  FROM
    `bracket`
  WHERE
    `bracket_source` = source AND
    `bracket_id` IN (
      SELECT
        `bracket_id`
      FROM
        `bracket_owners`
      WHERE
        `user_id` = userId
    );

END //
