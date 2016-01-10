import Router from './lib/router';

// Pages
import Landing from './views/landing';

Router.addRoutes({
  'index': Landing
});

Router.go(window.location.pathname);