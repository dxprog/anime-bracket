/**
 * Updates the order of brackets based upon popularity
 */
DROP PROCEDURE IF EXISTS `proc_GetNextRounds`;

DELIMITER //

CREATE PROCEDURE `proc_GetNextRounds` (bracketId INT)
BEGIN

    DECLARE currentTier INT;
    DECLARE currentGroup INT;
    DECLARE lastRoundInCurrentGroup INT;

    SELECT
        `round_tier`, `round_group` INTO currentTier, currentGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_final` = 0
        AND `round_deleted` = 0
    ORDER BY
        `round_tier` ASC,
        `round_group` ASC;

    SELECT
        MAX(round_id) INTO lastRoundInCurrentGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_tier` = currentTier
        AND `round_group` = currentGroup
        AND `round_final` = 0
        AND `round_deleted` = 0;

    SELECT
        MIN(`round_tier`) AS nextTier,
        (MIN(`round_tier`) * MAX(`round_group`) + MIN(`round_group`)) AS calculatedGroup,
        `round_group` AS nextGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_final` = 0
        AND `round_id` > lastRoundInCurrentGroup
        AND `round_deleted` = 0
    GROUP BY
        `round_group`,
        `round_tier`
    ORDER BY
        nextTier ASC,
        calculatedGroup ASC
    LIMIT 1;

END //