import Route from 'lib/route';
import Router from 'lib/router';

import Characters from './admin/characters';
import Nominee from './admin/nominee';

export default Route('admin', {

  initRoute() {
    Router.addRoutes({
      '/me/process/:perma/characters/': Characters,
      '/me/process/:perma/nominations/': Nominee
    });
    Router.go(window.location.pathname);
  }

});