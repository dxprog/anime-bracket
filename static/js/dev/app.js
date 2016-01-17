import Router from './lib/router';

// Pages
import Landing from './views/landing';
import BracketDisplay from './views/bracket-display';
import Voting from './views/voting';
import Nominations from './views/nominations';
import Characters from './views/characters';

Router.addRoutes({
  'index': Landing,
  '/results/:perma/': BracketDisplay,
  '/vote/:perma/': Voting,
  '/nominate/:perma/': Nominations,
  '/characters/:perma/': Characters
});

Router.go(window.location.pathname);