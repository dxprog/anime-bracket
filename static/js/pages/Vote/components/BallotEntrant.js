import React from 'react';

export const BallotEntrant = ({ roundId, id, voted, image, name, source, meta }) => (
  <>
    <input
      type="radio"
      name={`round:${roundId}`}
      id={`entrant${id}`}
      checked={voted}
      disabled={voted}
      className="character-input"
      autoComplete="off"
    />
    <label htmlFor={`entrant${id}`} className="mini-card__content">
      <img src={image} alt={name} className="mini-card__image" />
      <div className="mini-card__name">{name}</div>
      {source && <div className="mini-card__source">{source}</div>}
    </label>
    {meta && (
      <a
        className={`mini-card__meta mini-card__meta--${meta.type}`}
        href={meta.link}
        target="_blank"
      >
        {meta.link}
      </a>
    )}
  </>
);
