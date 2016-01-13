import Router from './lib/router';

// Pages
import Landing from './views/landing';
import BracketDisplay from './views/bracket-display';

Router.addRoutes({
  'index': Landing,
  '/results/:perma/': BracketDisplay
});

Router.go(window.location.pathname);