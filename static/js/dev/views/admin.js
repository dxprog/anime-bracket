import Route from '../lib/route';
import Router from '../lib/router';

import Characters from './admin/characters';
//import Nominee from './nominee';

export default Route('admin', {

  initRoute() {
    // This is kind of an ugly workaround for now. Weighting should be added later
    // so that anything with a wildcard is weigted lower
    Router.removeRoute('/me/*');
    Router.addRoutes({
      '/me/process/:perma/characters/': Characters
    });
    Router.go(window.location.pathname);
  }

});