import $ from 'jquery';

import { Route } from 'molecule-router';

import hexRound from '@views/hexRound.hbs';

const HEX_WIDTH = 132;
const HEX_HEIGHT = 113;
const TOP_PADDING = 77;
const LEFT_PADDING = 65;
const FORWARD = 'forward';
const BACKSLASH = 'backslash';
const BAIL_TRIES = 10;

export default Route('landing', {
  initRoute() {
    const $window = $(window);
    const data = window.rounds || [];
    let out = '';

    const cols = this._cols = Math.floor(($window.width() - LEFT_PADDING) / HEX_WIDTH);
    const rows = this._rows = Math.floor(($window.height() - TOP_PADDING) / HEX_HEIGHT);
    let count = cols * rows;
    this._grid = new Array(count);
    this._itr = 0;

    data.forEach((item) => {
      if (count-- > -1) {
        out += this.placeHex(item);
      }
    });

    $('#hexes').html(out);
  },

  getRandomPosition() {
    const grid = this._grid;
    const cols = this._cols;
    const rows = this._rows;
    let x = Math.round(Math.random() * cols);
    let y = Math.round(Math.random() * rows);
    let bail = 0;

    while (typeof grid[y * rows + x] === 'object' && bail < BAIL_TRIES) {
      x = Math.round(Math.random() * cols);
      y = Math.round(Math.random() * rows);
      bail++;
    }

    return bail < BAIL_TRIES ? y * rows + x : null;
  },

  placeHex(round) {
    const grid = this._grid;
    const cols = this._cols;
    const index = this.getRandomPosition();
    const y = index ? Math.floor(index / cols) : null;
    const x = index ? index - (y * cols) : null;
    const direction = Math.random() > 0.5 ? BACKSLASH : FORWARD;
    let xOffset = direction === FORWARD ? -(HEX_WIDTH / 2) : 0;
    let retVal = '';

    if (null !== index) {
      xOffset += y % 2 > 0 ? HEX_WIDTH / 2 : 0;

      grid[(y + 1) * cols + (y % 2 > 0 ? -1 : 0) + x] = {};
      grid[index] = {
        character1: round.character1,
        character2: round.character2,
        x: x * HEX_WIDTH + LEFT_PADDING + xOffset,
        y: y * HEX_HEIGHT + TOP_PADDING,
        direction,
        fadeDelay: this._itr++
      };
      retVal = hexRound(grid[index]);
    }

    return retVal;
  }

});
