import React, { useMemo, useState } from 'react';
import ReactDOM from 'react-dom';
import { Route } from 'molecule-router';

import { BracketState } from '@src/constants';

import './EntrantStats.scss';

function getRoundTitle(roundTier, totalRounds) {
  if (roundTier === totalRounds) {
    return 'the Finals';
  } else if (roundTier === totalRounds - 1) {
    return 'the Semi-finals';
  }

  return `Round ${roundTier}`;
}

function getLostByText(lostBy) {
  const lostByInt = parseInt(lostBy);
  if (lostByInt === 0) {
    return 'tie rule';
  } else if (lostByInt === 1) {
    return '1 vote';
  }

  return `${lostByInt.toLocaleString()} votes`;
}

const EntrantRow = ({ entrant, totalRounds, bracketEnded }) => {
  const { character, lostTo, totalVotes, group } = entrant;

  // don't show eliminations characters
  if (!character.seed) {
    return null;
  }

  return (
    <tr className="entrant-stats__row">
      <td className="entrant-stats__col entrant-stats__col--image">
        <img src={character.image} alt={character.name} className="entrant-stats__image" />
      </td>
      <td className="entrant-stats__col entrant-stats__col--name">
        {character.name}
      </td>
      <td className="entrant-stats__col entrant-stats__col--seed">
        {character.seed}
      </td>
      <td className="entrant-stats__col entrant-stats__col--group">
        {group}
      </td>
      <td className="entrant-stats__col entrant-stats__col--votes">
        {parseInt(totalVotes).toLocaleString()}
      </td>
      <td className="entrant-stats__col entrant-stats__col--lost-to">
        {lostTo && (
          <p>
            <strong>
              {lostTo.character.name}
            </strong>
            {' in '}
            <strong>{getRoundTitle(parseInt(lostTo.round.tier), totalRounds)}</strong>
            {' by '}
            <strong>{getLostByText(lostTo.lostBy)}</strong>
          </p>
        )}
        {(!lostTo && bracketEnded) && (
          <p><strong>Winner</strong></p>
        )}
      </td>
    </tr>
  );
};

const EntrantStats = ({ bracket, entrants }) => {
  const [ totalRounds, setTotalRounds ] = useState(0);
  const bracketEnded = bracket.state === BracketState.Final;

  // calculate the number of rounds in the bracket
  useMemo(() => {
    // break the entrants down into individual groups and their counts
    const bracketGroupInfo = entrants.reduce((acc, entrant) => {
      if (entrant.character.seed) {
        if (!acc[entrant.group]) {
          acc[entrant.group] = 0;
        }
        acc[entrant.group]++;
      }
      return acc;
    }, {});

    // the log2 of the total number of entrants (counted from all rounds) will
    // be the total number of rounds in the bracket
    setTotalRounds(Math.log2(
      Object
        .keys(bracketGroupInfo)
        .reduce((totalEntrants, groupKey) => bracketGroupInfo[groupKey] + totalEntrants, 0)
    ));
  }, [ entrants ]);

  return (
    <table class="entrant-stats">
      <thead>
        <tr>
          <th className="entrant-stats__header entrant-stats__header--image"></th>
          <th className="entrant-stats__header entrant-stats__header--name">Name</th>
          <th className="entrant-stats__header entrant-stats__header--seed">Seed</th>
          <th className="entrant-stats__header entrant-stats__header--group">Grp</th>
          <th className="entrant-stats__header entrant-stats__header--votes">Votes</th>
          <th className="entrant-stats__header entrant-stats__header--lost-to">Lost To</th>
        </tr>
      </thead>
      <tbody>
        {entrants.map(entrant => (
          <EntrantRow entrant={entrant} totalRounds={totalRounds} bracketEnded={bracketEnded} />
        ))}
      </tbody>
    </table>
  );
};

// ...I hate this route nonsense...
export default Route('entrant-stats', {
  initRoute() {
    const { bracket, entrants } = window._appData;
    ReactDOM.render((
      <EntrantStats bracket={bracket} entrants={entrants} />
    ), document.getElementById('reactApp'));
  }
});
