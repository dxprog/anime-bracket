import React from 'react';
import ReactDOM from 'react-dom';
import { Route } from 'molecule-router';

import { AuthContextProvider, useAuth } from '@src/hooks/useAuth';

import { BallotEntrant } from './components/BallotEntrant';

const Vote = ({ rounds, bracket, showCaptcha }) => {
  const { csrfToken } = useAuth();

  return (
    <>
      <p className="message hidden"></p>
      <form action="/submit/?action=vote" method="post" id="vote-form">
        <ul className="voting mini-card-container">
          {rounds.map(round => {
            const { character1, character2 } = round;
            return (
              <>
                <li
                  className="mini-card mini-card--left entrant1"
                  key={`entrant-${character1.id}`}
                >
                  <BallotEntrant roundId={round.id} {...character1} />
                </li>
                <li
                  className="mini-card mini-card--right entrant2"
                  key={`entrant-${character2.id}`}
                >
                  <BallotEntrant roundId={round.id} {...character2} />
                </li>
              </>
            );
          })}
        </ul>
        {showCaptcha && (
          <div class="captcha">
            <script src="https://www.google.com/recaptcha/api.js"></script>
            <div class="g-recaptcha" data-sitekey="6LdLPWgUAAAAAMWUFDYKtMFz0ppFaWI6DbEarLjj"></div>
          </div>
        )}
        <input type="hidden" name="bracketId" value={bracket.id} />
        <input type="hidden" name="_auth" value={csrfToken} />
        <button type="submit" className="button">
          Submit Votes
        </button>
      </form>
    </>
  );
};

// ...I hate this route nonsense...
export default Route('vote', {
  initRoute() {
    const { bracket, round, userId, csrfToken } = window._appData;
    ReactDOM.render((
      <AuthContextProvider value={{ userId, csrfToken }}>
        <Vote bracket={bracket} rounds={round} />
      </AuthContextProvider>
    ), document.getElementById('reactApp'));
  }
});
