import { useState, useMemo } from 'react';

const BRACKETS_PER_PAGE = 20;

export default function useBracketFilters(brackets) {
  const [ filteredBrackets, setFilteredBrackets ] = useState([]);
  const [ searchString, setSearchString ] = useState('');
  const [ page, setPage ] = useState(0);
  const [ displayBrackets, setDisplayBrackets ] = useState([]);

  useMemo(() => {
    if (!searchString) {
      setFilteredBrackets([ ...brackets ]);
    } else {
      setFilteredBrackets(brackets.filter(bracket => {
        const search = searchString.toLowerCase();
        return (
          bracket.name.toLowerCase().indexOf(search) > -1 ||
          (bracket.winner && bracket.winner.name.toLowerCase().indexOf(search) > -1)
        );
      }));
    }
  }, [ brackets, searchString ]);

  useMemo(() => {
    const startIndex = page * BRACKETS_PER_PAGE;
    if (filteredBrackets.length) {
      setDisplayBrackets([ ...filteredBrackets ].slice(startIndex, startIndex + BRACKETS_PER_PAGE));
    }
  }, [ filteredBrackets, page ]);

  return {
    displayBrackets,
    page,
    setPage,
    setSearchString,
    numPages: Math.ceil(filteredBrackets.length / BRACKETS_PER_PAGE),
  };
}
