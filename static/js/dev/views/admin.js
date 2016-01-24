import Route from 'lib/route';
import Router from 'lib/router';

import Characters from './admin/characters';
import Nominee from './admin/nominee';
import StartBracket from './admin/start-bracket';
import Stats from './admin/stats';

export default Route('admin', {

  initRoute() {
    Router.addRoutes({
      '/me/process/:perma/characters/': Characters,
      '/me/process/:perma/nominees/': Characters,
      '/me/process/:perma/nominations/': Nominee,
      '/me/start/:perma/voting/': StartBracket,
      '/me/stats/:perma/': Stats
    });
    Router.go(window.location.pathname);
  }

});