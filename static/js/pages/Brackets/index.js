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

  const handlePageChange = page => {
    setPage(page);
    window.scroll({ top: 0 });
  }

  return (
    <>
      <header>
        <h2>{title}</h2>
        <input
          type="text"
          className="input input--search"
          onChange={evt => handleSearch(evt.target.value)}
          placeholder="Search brackets and winners"
        />
      </header>
      <ul className="brackets">
        {displayBrackets.map(bracket => (
          <BracketCard bracket={bracket} />
        ))}
      </ul>
      <div class="brackets-pagination">
        <button
          onClick={() => handlePageChange(page - 1)}
          disabled={page === 0}
          className="button button--small button--back"
        >
          Previous Page
        </button>
        <span className="brackets-pagination__page">
          {page + 1} of {numPages}
        </span>
        <button
          onClick={() => handlePageChange(page + 1)}
          disabled={page + 1 === numPages}
          className="button button--small button--next"
        >
          Next Page
        </button>
      </div>
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
