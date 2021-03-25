import React, { useEffect, useState } from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';
import { Route } from 'molecule-router';

import EntrantMiniCard from '../components/EntrantMiniCard';
import { CharacterPropTypes } from '../utils/propTypes';

const sortEntrantsByName = entrants => {
  const newEntrants = Array.from(entrants);
  return [
    {
      title: 'Entrant Roster',
      entrants: newEntrants.sort((a, b) => a.name < b.name ? -1 : 1),
    }
  ];
};

const EntrantsView = ({ bracket, characters }) => {
  const [ entrantsList, setEntrantsList ] = useState([]);
  const [ sortKey, setSortKey ] = useState(null);

  // Initial population of the entrants list, sorted by name
  useEffect(() => {
    setEntrantsList(sortEntrantsByName(characters));
  }, []);

  return (
    <>
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
    </>
  );
};

EntrantsView.propTypes = {
  characters: PropTypes.arrayOf(CharacterPropTypes).isRequired,
};

export default Route('characters', {

  initSortData() {

    const characters = this._characters;

    let sourceSort = this._sourceSort = {};
    let seededSort = this._seededSort = new Array(characters.length);
    let nonSeedItr = characters.length;
    let maxSeed = 0;

    characters.forEach((character) => {

      if (!sourceSort.hasOwnProperty(character.source)) {
        sourceSort[character.source] = [];
      }
      sourceSort[character.source].push(character)

      if (character.seed) {
        seededSort[character.seed - 1] = character;
        maxSeed = character.seed > maxSeed ? character.seed : maxSeed;
      } else {
        // Push non-seeded characters onto the back of the stack
        seededSort[--nonSeedItr] = character;
      }

    });

    this._maxSeed = maxSeed;

  },

  resortEntrants(evt) {
    const seededSort = this._seededSort;
    const sourceSort = this._sourceSort;
    const maxSeed = this._maxSeed;

    let sortBy = $(evt.currentTarget).val();
    let dataSets = [];

    switch (sortBy) {
      case 'seed':
        dataSets.push({
          header: 'Seeded Entrants',
          characters: seededSort.slice(0, maxSeed)
        });
        dataSets.push({
          header: 'Eliminated Entrants',
          characters: seededSort.slice(maxSeed)
        });
        break;
      case 'source':
        for (var source in sourceSort) {
          if (!!sourceSort[source]) {
            dataSets.push({
              header: source,
              characters: sourceSort[source]
            });
          }
        }
        break;
      default:
        dataSets.push({
          characters: this._characters
        });
        break;
    }

    var out = '';
    dataSets.forEach(function(item) {
      out += characterList(item);
    });

    this._$roster.html(out);
  },

  initRoute() {
    this._characters = window._characters;
    // this.initSortData();
    // this._$roster = $('#roster');
    // $('[name="sort"]').on('change', this.resortEntrants.bind(this));
    // Handlebars.registerHelper('metaLabel', this.metaLabelHelper.bind(this));
    ReactDOM.render(
      <EntrantsView characters={window._characters} />,
      document.getElementById('roster')
    );
  }

});
