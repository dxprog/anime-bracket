/**
 * Rolls a bracket back to a previous round
 */
DROP PROCEDURE IF EXISTS `proc_RollbackBracket`;

DELIMITER //

CREATE PROCEDURE `proc_RollbackBracket` (
  bracketId INT,
  roundTier INT,
  roundGroup INT
)
BEGIN

  /* Delete rounds in the next tier after (and including) the group to roll back to and all tiers after that */
  UPDATE
    `round`
  SET
    `round_deleted` = 1
  WHERE
    `bracket_id` = bracketId
    AND (
      (
        `round_tier` = roundTier + 1
        AND `round_group` >= roundGroup
      )
      OR `round_tier` > roundTier + 1
    );

  /* Set the new tier/group and all groups after as non-finalized and clear any calculated vote counts */
  UPDATE
    `round`
  SET
    `round_final` = 0,
    `round_character1_votes` = NULL,
    `round_character2_votes` = NULL,
    `round_end_date` = NULL
  WHERE
    `bracket_id` = bracketId
    AND `round_deleted` = 0
    AND (
      (
        `round_tier` = roundTier
        AND `round_group` >= roundGroup
      )
      OR `round_tier` > roundTier
    );

  /* Finally, delete all votes from the non-finalized rounds */
  DELETE FROM
    `votes`
  WHERE
    `round_id` IN (
      SELECT
        `round_id`
      FROM
        `round`
      WHERE
        `bracket_id` = bracketId
        AND `round_final` = 0
        AND `round_deleted` = 0
    );

  /* If rolling back to an eliminations round, kill any actual bracket setup */
  IF roundTier = 0 THEN
    BEGIN
      UPDATE
        `round`
      SET
        `round_deleted` = 1
      WHERE
        `bracket_id` = bracketId
        AND `round_tier` > 0;
    END;
  END IF;

END //
