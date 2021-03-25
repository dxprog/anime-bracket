import React from 'react';
import PropTypes from 'prop-types';

import { CharacterPropTypes } from '../../utils/propTypes';
import { metaLabel } from '../../utils/meta';

{/* <li class="mini-card">
<div class="mini-card__content">
    <img
        src="{{image}}"
        alt="{{name}}"
        class="mini-card__image"
    />
    <div class="mini-card__name">{{name}}</div>
    <div class="mini-card__source">{{source}}</div>
    {{#if seed}}
        <div class="mini-card__seed">{{seed}}</div>
    {{/if}}
</div>
{{#if meta}}
    <a
        class="mini-card__meta mini-card__meta--{{meta.type}}"
        href="{{meta.link}}"
        target="_blank"
    >
        {{metaLabel meta}}
    </a>
{{/if}}
</li> */}

const EntrantMiniCard = ({ entrant, tagName = 'div' }) => {
  const TagName = tagName;

  return (
    <TagName className="mini-card">
      <div className="mini-card__content">
        <img
          src={entrant.image}
          alt={entrant.name}
          className="mini-card__image"
        />
        <div className="mini-card__name">{entrant.name}</div>
        {entrant.source ? (
          <div className="mini-card__source">{entrant.source}</div>
        ) : null}
        {entrant.seed ? <div className="mini-card__seed">{entrant.seed}</div> : null}
      </div>
      {entrant.meta ? (
        <a
          className={`mini-card__meta mini-card__meta--${entrant.meta.type}`}
          href={entrant.meta.link}
          target="_blank"
        >
          {metaLabel(entrant.meta)}
        </a>
      ) : null}
    </TagName>
  );
};

EntrantMiniCard.propTypes = {
  entrant: CharacterPropTypes.isRequired,
};

export default EntrantMiniCard;
