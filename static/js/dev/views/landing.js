import $ from 'jquery';

import hexRound from '../../../../views/hexRound.hbs';

const HEX_WIDTH = 132;
const HEX_HEIGHT = 113;
const TOP_PADDING = 77;
const LEFT_PADDING = 65;
const FORWARD = 'forward';
const BACKSLASH = 'backslash';
const BAIL_TRIES = 10;

let data = window.rounds || [];
let cols = 0;
let rows = 0;
let grid = [];

let itr = 0;

function getRandomPosition() {

    var x = Math.round(Math.random() * cols),
        y = Math.round(Math.random() * rows),
        bail = 0;

    while (typeof grid[y * rows + x] === 'object' && bail < BAIL_TRIES) {
        x = Math.round(Math.random() * cols);
        y = Math.round(Math.random() * rows);
        bail++;
    }

    return bail < BAIL_TRIES ? y * rows + x : null;

};

function placeHex(round) {
    var index = getRandomPosition(),
        y = index ? Math.floor(index / cols) : null,
        x = index ? index - (y * cols) : null,
        direction = Math.random() > 0.5 ? BACKSLASH : FORWARD,
        xOffset = direction === FORWARD ? -(HEX_WIDTH / 2) : 0,
        retVal = '';

    if (null !== index) {
        xOffset += y % 2 > 0 ? HEX_WIDTH / 2 : 0;

        grid[(y + 1) * cols + (y % 2 > 0 ? -1 : 0) + x] = {};
        grid[index] = {
            character1: round.character1,
            character2: round.character2,
            x: x * HEX_WIDTH + LEFT_PADDING + xOffset,
            y: y * HEX_HEIGHT + TOP_PADDING,
            direction: direction,
            fadeDelay: itr++
        };
        retVal = hexRound(grid[index]);
    }

    return retVal;
};

function init() {
    var $window = $(window),
        out = '',
        count = 0;

    cols = Math.floor(($window.width() - LEFT_PADDING) / HEX_WIDTH);
    rows = Math.floor(($window.height() - TOP_PADDING) / HEX_HEIGHT);
    count = cols * rows;
    grid = new Array(cols * rows);

    data.forEach(function(item) {
        if (count-- > -1) {
            out += placeHex(item);
        }
    });

    $('#hexes').html(out);

};

export default init;