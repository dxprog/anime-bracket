import $ from 'jquery';

import { Route } from 'molecule-router';

import characterList from 'templates/partials/_characterList.hbs';

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
    this.initSortData();
    this._$roster = $('#roster');
    $('[name="sort"]').on('change', this.resortEntrants.bind(this));
  }

});