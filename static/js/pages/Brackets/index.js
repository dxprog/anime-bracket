import React from 'react';
import ReactDOM from 'react-dom';
import { Route } from 'molecule-router';

import BracketCard from './components/BracketCard';

const Brackets = ({ brackets, title }) => {
  return (
    <>
      <header>
        <h2>{title}</h2>
      </header>
      <ul className="brackets">
        {brackets.map(bracket => (
          <BracketCard bracket={bracket} />
        ))}
      </ul>
    </>
  );
};

// ...I hate this route nonsense...
export default Route('brackets', {
  initRoute() {
    const { brackets, title } = window._appData;
    ReactDOM.render((
      <Brackets brackets={brackets} title={title} />
    ), document.getElementById('reactApp'));
  }
});
