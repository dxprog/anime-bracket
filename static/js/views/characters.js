import React, { useEffect, useState } from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';
import { Route } from 'molecule-router';

import EntrantMiniCard from '../components/EntrantMiniCard';
import { CharacterPropTypes } from '../utils/propTypes';
import { BracketStates, sourceEnabled } from '../utils/bracket';

const SortKeys = Object.freeze({
  Name: 'name',
  Source: 'source',
  Seed: 'seed',
});

const sortEntrantsByName = entrants => {
  const newEntrants = Array.from(entrants);
  return [
    {
      title: 'Entrant Roster',
      entrants: newEntrants.sort((a, b) => a.name < b.name ? -1 : 1),
    }
  ];
};

const sortEntrantsBySource = entrants => {
  const sources = entrants.reduce((acc, entrant) => {
    if (!acc[entrant.source]) {
      acc[entrant.source] = [];
    }
    acc[entrant.source].push(entrant);
    return acc;
  }, {});
  return Object.keys(sources).map(source => (
    {
      title: source,
      entrants: sources[source].sort((a, b) => a.name < b.name ? -1 : 1),
    }
  )).sort((a, b) => a.title < b.title ? -1 : 1);
};

const EntrantsView = ({ bracket, characters }) => {
  const [ entrantsList, setEntrantsList ] = useState([]);
  const [ sortKey, setSortKey ] = useState(SortKeys.Name);

  useEffect(() => {
    switch (sortKey) {
      case SortKeys.Source:
        setEntrantsList(sortEntrantsBySource(characters));
        break;
      case SortKeys.Name:
      default:
        setEntrantsList(sortEntrantsByName(characters));
        break;
    }
  }, [sortKey]);

  const hasSeededEntrants = bracket.state === BracketStates.Voting || bracket.state === BracketStates.Final;

  return (
    <div className="entrants-page">
      <ul className="sorter">
        <li className="label">Sort By:</li>
        <li>
            <input
              type="radio"
              name="sort"
              id="nameSort"
              value={SortKeys.Name}
              checked={sortKey === SortKeys.Name}
              onChange={e => setSortKey(e.target.value)}
            />
            <label htmlFor="nameSort">Entrant Name</label>
        </li>
        {hasSeededEntrants && (
          <li>
            <input
              type="radio"
              name="sort"
              id="seedSort"
              value={SortKeys.Seed}
              checked={sortKey === SortKeys.Seed}
              onChange={e => setSortKey(e.target.value)}
            />
            <label htmlFor="seedSort">Seed</label>
          </li>
        )}
        {sourceEnabled(bracket) && (
          <li>
            <input
              type="radio"
              name="sort"
              id="sourceSort"
              value={SortKeys.Source}
              checked={sortKey === SortKeys.Source}
              onChange={e => setSortKey(e.target.value)}
            />
            <label htmlFor="sourceSort">{bracket.sourceLabel}</label>
          </li>
        )}
      </ul>
      {entrantsList.map(list => (
        <React.Fragment key={`section-${list.title}`}>
          <h3>{`${list.title} - ${list.entrants.length} entrants`}</h3>
          <ul className="characters mini-card-container">
            {list.entrants.map(entrant => (
              <EntrantMiniCard key={`entrant-${entrant.id}`} entrant={entrant} tagName="li" />
            ))}
          </ul>
        </React.Fragment>
      ))}
    </div>
  );
};

EntrantsView.propTypes = {
  characters: PropTypes.arrayOf(CharacterPropTypes).isRequired,
};

export default Route('characters', {
  initRoute() {
    ReactDOM.render(
      <EntrantsView
        characters={window.bootstrap.characters}
        bracket={window.bootstrap.bracket}
      />,
      document.getElementById('roster')
    );
  }
});
