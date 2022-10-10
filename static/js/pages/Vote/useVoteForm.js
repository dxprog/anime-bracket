import React, { useState, useMemo } from 'react';

export const useVoteForm = ({ rounds, bracket }) => {
  const [ ballot, setBallot ] = useState([]);
  const [ loading, setLoading ] = useState(false);

  // re-map the initial data as { ...roundId: roundData }
  useMemo(() => {
    setBallot(rounds.reduce((acc, round) => {
      acc[round.id] = {
        ...round,
        character1: {
          ...round.character1,
          selected: round.character1.voted,
        },
        character2: {
          ...round.character2,
          selected: round.character2.voted,
        },
      };
      return acc;
    }, {}));
  }, rounds);

  const selectEntrant = ({ roundId, entrantId }) => {
    const { character1, character2, ...roundProps } = ballot[roundId];


    // if the user has already cast a vote in this round, nope
    if (roundProps.voted) {
      return;
    }

    const updatedRound = {
      ...roundProps,
      // update the selected state of each character such that:
      // - it was the character marked as selected
      // - that character wasn't _already_ selected. if they are, unselect
      character1: {
        ...character1,
        selected: character1.id === entrantId && !character1.selected,
      },
      character2: {
        ...character2,
        selected: character2.id === entrantId && !character2.selected,
      },
    };

    setBallot({ ...ballot, [roundId]: updatedRound });
  };
  // round:312787: 231636
  // bracketId: 6433
  // _auth: 64ede22054670f1b
  const submitVotes = async (csrfToken) => {
    const formData = new FormData();
    setLoading(true);

    Object.keys(ballot).forEach(roundId => {
      const round = ballot[roundId];
      if (!round.character1.voted && round.character1.selected) {
        formData.append(`round:${roundId}`, round.character1.id);
      } else if (!round.character2.voted && round.character2.selected) {
        formData.append(`round:${roundId}`, round.character2.id);
      }
    });

    formData.append('bracketId', bracket.id);
    formData.append('_auth', csrfToken);

    const data = await fetch('/submit/?action=vote', {
      method: 'POST',
      body: formData,
    }).then(response => response.json());

    setLoading(false);
    return data;
  };

  return {
    ballot,
    loading,
    selectEntrant,
    submitVotes,
  }
};
