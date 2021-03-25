export const BracketStates = Object.freeze({
  NotStarted: 0,
  Nominations: 1,
  Eliminations: 2,
  Voting: 3,
  Wildcard: 4,
  Final: 5,
  Hidden: 6,
});

export function sourceEnabled(bracket) {
  return bracket.sourceLabel !== 'NO_SOURCE';
}
