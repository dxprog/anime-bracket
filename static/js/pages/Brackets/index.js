import React from 'react';
import ReactDOM from 'react-dom';
import { Route } from 'molecule-router';

import BracketCard from './components/BracketCard';
import useBracketFilters from './useBracketFilters';

const Brackets = ({ brackets, title }) => {
  const {
    displayBrackets,
    setPage,
    setSearchString,
    page,
    numPages,
  } = useBracketFilters(brackets);

  const handleSearch = term => {
    setSearchString(term);
    setPage(0);
  };

  return (
    <>
      <header>
        <h2>{title}</h2>
        <input onChange={evt => handleSearch(evt.target.value)} />
      </header>
      <ul className="brackets">
        {displayBrackets.map(bracket => (
          <BracketCard bracket={bracket} />
        ))}
      </ul>
      <button onClick={() => setPage(page - 1)} disabled={page === 0}>Previous Page</button>
      <span>{page + 1} of {numPages}</span>
      <button onClick={() => setPage(page + 1)} disabled={page + 1 === numPages}>Next Page</button>
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
