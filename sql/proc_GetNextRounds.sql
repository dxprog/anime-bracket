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
        MIN(round_tier) INTO currentTier
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_final` = 0;

    SELECT
        MIN(round_group) INTO currentGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_tier` = currentTier
        AND `round_final` = 0;

    SELECT
        MAX(round_id) INTO lastRoundInCurrentGroup
    FROM
        `round`
    WHERE
        `bracket_id` = bracketId
        AND `round_tier` = currentTier
        AND `round_group` = currentGroup
        AND `round_final` = 0;

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
    GROUP BY
        `round_group`,
        `round_tier`
    ORDER BY
        nextTier ASC,
        calculatedGroup ASC
    LIMIT 1;

END //