/**
 * Updates the order of brackets based upon popularity
 */
DROP PROCEDURE IF EXISTS `proc_GetRoundWinner`;

DELIMITER //

CREATE PROCEDURE `proc_GetRoundWinner` (roundId INT)
BEGIN

    DECLARE character1Id INT;
    DECLARE character2Id INT;
    DECLARE character1Votes INT;
    DECLARE character2Votes INT;
    DECLARE character1Seed INT;
    DECLARE character2Seed INT;

    SELECT
        `round_character1_id`, `round_character2_id` INTO character1Id, character2Id
    FROM
        `round`
    WHERE
        `round_id` = roundId;

    SELECT
        COUNT(1) INTO character1Votes
    FROM
        `votes`
    WHERE
        `round_id` = roundId
        AND `character_id` = character1Id;

    SELECT
        COUNT(1) INTO character2Votes
    FROM
        `votes`
    WHERE
        `round_id` = roundId
        AND `character_id` = character2Id;

    CASE
        WHEN character1Votes > character2Votes THEN SELECT character1Id AS character_id;
        WHEN character2Votes > character1Votes THEN SELECT character2Id AS character_id;
        ELSE
            BEGIN

                SELECT
                    `character_seed` INTO character1Seed
                FROM
                    `character`
                WHERE
                    `character_id` = character1Id;

                SELECT
                    `character_seed` INTO character2Seed
                FROM
                    `character`
                WHERE
                    `character_id` = character2Id;

                IF character1Seed < character2Seed THEN
                    SELECT character1Id AS character_id;
                ELSE
                    SELECT character2Id AS character_id;
                END IF;

            END;
    END CASE;

END //