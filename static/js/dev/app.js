import Router from './lib/router';

// Pages
import Landing from './views/landing';
import BracketDisplay from './views/bracket-display';
import Voting from './views/voting';

Router.addRoutes({
  'index': Landing,
  '/results/:perma/': BracketDisplay,
  '/vote/:perma/': Voting
});

Router.go(window.location.pathname);