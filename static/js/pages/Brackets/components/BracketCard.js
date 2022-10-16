import React from 'react';

import { BracketState } from '@src/constants';

import CardImage from './CardImage';

const hasResults = bracket => [ BracketState.Voting, BracketState.Final ].includes(bracket.state);
const isVoting = bracket => [ BracketState.Eliminations, BracketState.Voting ].includes(bracket.state);

const BracketCard = ({ bracket }) => {
  return (
    <li className="bracket-card">
      <CardImage bracket={bracket} />
      <div className="info">
        {bracket.winner && (
          <h4>{bracket.name}</h4>
        )}
        {!bracket.winner && (
          <>
            <h3>{bracket.name}</h3>
            <h4>
              {bracket.title}
            </h4>
          </>
        )}
        <ul className="actions">
          {hasResults(bracket) && (
            <li>
              <a href={`/${bracket.perma}/results`}>Results</a>
            </li>
          )}
          {bracket.state === BracketState.Nominations && (
            <li><a href={`/${bracket.perma}/nominate`}>Nominate</a></li>
          )}
          {isVoting(bracket) && (
            <li><a href={`/${bracket.perma}/vote`}>Vote</a></li>
          )}
        </ul>
      </div>
    </li>
  );
};

export default BracketCard;
