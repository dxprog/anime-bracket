import Handlebars from 'handlebars/runtime';
import { Route, Router } from 'molecule-router';
import $ from 'jquery';

import Entrant from '../model/entrant';
import { default as Tier, ENTRANT_HEIGHT } from '../model/tier';

import TPL_GROUP_PICKER from 'templates/groupPicker.hbs';
import TPL_ENTRANT from 'templates/partials/_entrant.hbs';
import TPL_WINNER from 'templates/winner.hbs';

const SINGLETON_NAME = 'bracket-display';
const COLUMN_WIDTH = 225 + 18;

export default Route(SINGLETON_NAME,{

  __construct() {
    this._tiers = [];
    this._$content = $('.bracket-display');
    this._$body = $('body');
    this._$header = $('header');
    this._groups = 0;
    this._initialized = false;
  },

  parseQueryString(qs) {
    var
      retVal = {},
      i = null,
      count = 0,
      kvp = null;

    if (!qs) {
      qs = location.href.indexOf('?') !== -1 ? location.href.split('?')[1] : null;
    }

    if (qs) {
      qs = qs.split('&');
      for (i = 0, count = qs.length; i < count; i++) {
        kvp = qs[i].split('=');
        retVal[kvp[0]] = kvp.length === 1 ? true : decodeURIComponent(kvp[1]);
      }
    }

    return retVal;
  },

  renderBracket(group, tier) {
    let left = '';
    let right = '';
    let temp = [];
    let columns = 0;
    let max = this.tiersForGroup(group);
    let lastRound = null;
    let bracketHeight = 0;
    let winner = {};

    tier = tier || 0;
    bracketHeight = Math.pow(2, max - tier - 1) * ENTRANT_HEIGHT;

    for (let i = tier; i < max; i++) {
      temp = this._tiers[i].render(i - tier, group, true);
      left += temp[0];
      right = temp[1] + right;
      columns += 2;
    }

    // Render the winner
    lastRound = this._tiers[max - 1].getRound(0, group);
    if (null !== lastRound && null !== lastRound.entrant1 && null != lastRound.entrant2) {
      if (!lastRound.entrant1.votes && !lastRound.entrant2.votes) {
        winner = { entrant:new Entrant(null, 0) };
      } else {
        if (lastRound.entrant1.votes > lastRound.entrant2.votes) {
          winner = { entrant: lastRound.entrant1 };
        } else if (lastRound.entrant1.votes < lastRound.entrant2.votes) {
          winner = { entrant: lastRound.entrant2 };
        } else {
          // In a tie scenario, use seed to determine winner.
          winner = {
            entrant: (
              lastRound.entrant1.seed < lastRound.entrant2.seed ?
                lastRound.entrant1 : lastRound.entrant2
            )
          }
        }
      }
      winner.height = bracketHeight;
      left += TPL_WINNER(winner);
    }

    // Add an additional column for the winner
    this._$content.width(++columns * COLUMN_WIDTH).html(left + right);
  },

  /**
   * Returns the number of tiers that will be in a group
   */
  tiersForGroup(group) {
    var rounds = this._tiers[0].getRoundsForGroup(group).length;
    return Math.log(rounds) / Math.LN2 + 1;
  },

  handleMouseOver(evt) {
    let id = evt.currentTarget.getAttribute('data-id');
    if ('1' !== id) {

      $('.highlighted').removeClass('highlighted');
      $('.entrant[data-id="' + id + '"]')
        .addClass('highlighted')
        .parent().addClass('highlighted');
      }
  },

  handleGroupChange(e) {
    this.changeGroup($(e.currentTarget).data('group'));
  },

  changeGroup(group, ignoreHistory) {
    let tier = null;
    let urlGroup = group;

    const bracketData = this._bracketData;

    this._$header.find('.selected').removeClass('selected');
    this._$header.find('[data-group="' + group + '"]').addClass('selected');

    if (group === 'finals') {
      group = null;
      tier = bracketData.results.length - 3;
    } else if (group === 'full') {
      group = null;
      tier = 0;
    } else {
      group = parseInt(group, 10);
      urlGroup = group + 1;
    }
    this.renderBracket(group, tier);

    if (!ignoreHistory) {
      Router.go('results.perma', { perma: bracketData.perma, group: urlGroup });
    }
  },

  populateGroups() {
    let out = [];

    for (let i = 0; i < this._groups; i++) {
      out.push({ name:String.fromCharCode(i + 65), index:i });
    }

    this._$header
      .find('ul.groups')
      .html(TPL_GROUP_PICKER({ groups:out }))
      .on('click', 'li', this.handleGroupChange.bind(this));
  },

  initRoute() {

    let qs = this.parseQueryString();
    let group = qs.hasOwnProperty('group') ? qs.group : 1;
    let groups = 0;

    this._bracketData = window.bracketData || null;


    if (this._bracketData && !this._initialized) {

      const bracketData = this._bracketData;

      Handlebars.registerPartial('entrant', TPL_ENTRANT);
      Handlebars.registerHelper('userVoted', function(entrant, options) {
        var retVal = '',
          id = '' + this.id;
        if (bracketData.userVotes && bracketData.userVotes.hasOwnProperty(id)) {
          retVal = bracketData.userVotes[id] == entrant.id ? options.fn(this) : '';
        }
        return retVal;
      });

      for (let i = 0, count = bracketData.results.length; i < count; i++) {
        let tier = new Tier(bracketData.results[i]);
        groups = tier.groups > groups ? tier.groups : groups;
        this._tiers.push(tier);
      }

      // Increment by 1 because group IDs are 0 based
      this._groups = groups + 1;

      this._$body.on('mouseover', '.entrant-info', this.handleMouseOver.bind(this));
      this._$header.find('.title').text(window.bracketData.name);

      group = isNaN(group) ? group : group - 1;
      this.populateGroups();
      this.changeGroup(group, true);
    } else if (this._initialized) {
      this.changeGroup(group, true);
    }
  }
});