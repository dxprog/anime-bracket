import React from 'react';
import classnames from 'classnames';

export const BallotEntrant = ({
  roundId, id, voted, selected, image, name, source, meta
}) => (
  <>
    <div
      className={classnames(
        'mini-card__content',
        { 'mini-card__content--selected' : selected }
      )}
    >
      <img src={image} alt={name} className="mini-card__image" />
      <div className="mini-card__name">{name}</div>
      {source && <div className="mini-card__source">{source}</div>}
    </div>
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
