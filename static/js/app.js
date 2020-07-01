import { Router } from 'molecule-router';

// Pages
import BracketDisplay from './views/bracket-display';
import Characters from './views/characters';
import Landing from './views/landing';
import Nav from './views/nav';
import Nominations from './views/nominations';
import Voting from './views/voting';
import Admin from './views/admin';

Router.addRoutes({
  'index': Landing,
  '/results/:perma': BracketDisplay,
  '/vote/:perma': Voting,
  '/nominate/:perma': Nominations,
  '/characters/:perma': Characters,
  '/me': Admin,
  '/me/*': Admin
});

// Set the UTC cookie
if (document.cookie.indexOf('utcOffset') === -1) {
  // 1000 days in the future...
  let expires = new Date();
  expires.setTime(Date.now() + (86400 * 1000 * 1000));
  document.cookie = 'utcOffset=' + expires.getTimezoneOffset() + '; expires=' + expires.toGMTString() + '; path=/';
}

Nav.init();

// Strip the trailing slash from the path so that the router doesn't break
const path = window.location.pathname.replace(/\/$/, '');
Router.go(path);