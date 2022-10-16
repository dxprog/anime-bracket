import React from 'react';
import classnames from 'classnames';

const CardImage = ({ bracket }) => {
  const { cardImage, entrants, name, winner } = bracket;
//   {{#if cardImage}}
//   <img src="{{cardImage}}" alt="{{name}}" class="card-image" />
// {{else}}
//   {{#if entrants}}
//       <div class="card-entrants{{#if winner}} has-winner{{/if}}">
//           <div class="inner">
//               {{#each entrants}}
//                   <img src="{{image}}" alt="{{name}}" title="{{name}}" />
//               {{/each}}
//           </div>
//           {{#if winner}}
//               <div class="winner">
//                   <img src="{{winner.image}}" alt="{{winner.name}}" />
//                   <h3>
//                       {{winner.name}}
//                       <span>Winner</span>
//                   </h3>
//               </div>
//           {{/if}}
//       </div>
//   {{else}}
//       <div class="card-entrants">
//           <img src="/static/images/no_card_image.png" alt="no image" class="no-image" />
//       </div>
//   {{/if}}
// {{/if}}
console.log(bracket);
  return (
    <>
      {cardImage && (
        <img src={cardImage} alt={name} className="card-image" />
      )}
      {(!cardImage && entrants) && (
        <div className={classnames(
          'card-entrants',
          {
            'has-winner': !!winner,
          }
        )}>
          <div className="inner">
            {entrants.map(({ image, name }) => (
              <img src={image} alt={name} title={name} />
            ))}
          </div>
          {winner && (
            <div className="winner">
              <img src={winner.image} alt={winner.name} title={winner.name} />
              <h3>
                {winner.name}
                <span>Winner</span>
              </h3>
            </div>
          )}
        </div>
      )}
      {(!cardImage && !entrants) && (
        <div className="card-entrants">
          <img src="/static/images/no_card_image.png" alt="no image" className="no-image" />
        </div>
      )}
    </>
  );
};

export default CardImage;
