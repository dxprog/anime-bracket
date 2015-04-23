/**
 * Cleans out old, decrepit brackets
 */
DROP PROCEDURE IF EXISTS `proc_CleanBrackets`;

DELIMITER //

CREATE PROCEDURE `proc_CleanBrackets` ()
BEGIN

    /* Brackets in nomination phase will be reverted to "not started" after 30 days */
    UPDATE
        `bracket`
    SET
        `bracket_state` = 0
    WHERE
        `bracket_state` = 1
        AND `bracket_start` < (UNIX_TIMESTAMP() - 86400 * 30);

END //
