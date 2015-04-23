/**
 * Updates the order of brackets based upon popularity
 */
DROP PROCEDURE IF EXISTS `proc_UpdateBracketScores`;

DELIMITER //

CREATE PROCEDURE `proc_UpdateBracketScores` ()
BEGIN

    DECLARE bracketId INT;
    DECLARE score DECIMAL;

    DECLARE cursorDone BOOLEAN DEFAULT FALSE;
    DECLARE query CURSOR FOR

        SELECT
            v.`bracket_id`,
            (LOG10(COUNT(1)) + (AVG(v.`vote_date`) - (UNIX_TIMESTAMP() - 86400)) / 45000) * b.bracket_state AS score
        FROM
            `votes` v
        INNER JOIN
            `bracket` b ON b.`bracket_id` = v.`bracket_id`
        WHERE
            v.`vote_date` >= (UNIX_TIMESTAMP() - 86400)
            AND b.`bracket_state` IN (2, 3)
        GROUP BY
            v.`bracket_id`

        UNION

        SELECT
            n.`bracket_id`,
            LOG10(COUNT(1)) + (b.bracket_start - (UNIX_TIMESTAMP() - 86400)) / 45000 AS score
        FROM
            `nominee` n
        INNER JOIN
            `bracket` b ON b.`bracket_id` = n.`bracket_id`
        WHERE
            b.`bracket_start` >= (UNIX_TIMESTAMP() - 86400)
            AND b.`bracket_state` = 1
        GROUP BY
            n.`bracket_id`;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET cursorDone = TRUE;

    OPEN query;
    WHILE cursorDone = FALSE DO

        FETCH query INTO bracketId, score;

        UPDATE
            `bracket`
        SET
            `bracket_score` = score
        WHERE
            `bracket_id` = bracketId;

    END WHILE;

END //