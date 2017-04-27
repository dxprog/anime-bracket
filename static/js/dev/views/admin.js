import { Route, Router } from 'molecule-router';
import $ from 'jquery';

import Characters from './admin/characters';
import Nominee from './admin/nominee';
import StartBracket from './admin/start-bracket';
import Stats from './admin/stats';

export default Route('admin', {

  initRoute() {
    const $brackets = $('.brackets');

    // If we're on the main brackets page, do that stuff, otherwise
    // kick in the admin routes
    if ($brackets.length) {
      $brackets.on('click', '.button.open', this.openActions.bind(this));
      $brackets.on('click', '.button.delete', this.confirmDelete.bind(this));
    } else {
      Router.addRoutes({
        '/me/process/:perma/characters/': Characters,
        '/me/process/:perma/nominees/': Characters,
        '/me/counts/:perma/': Characters,
        '/me/process/:perma/nominations/': Nominee,
        '/me/start/:perma/voting/': StartBracket,
        '/me/stats/:perma/': Stats
      });
      Router.go(window.location.pathname);
    }
  },

  openActions(evt) {
    $(evt.currentTarget).closest('li').toggleClass('open');
  },

  confirmDelete(evt) {
    if (!confirm('All data related to this bracket will be PERMENENTLY DELETED! Do you wish to continue?')) {
      evt.preventDefault();
      return false;
    }
  }

});