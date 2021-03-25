import PropTypes from 'prop-types';

export const CharacterPropTypes = PropTypes.shape({
  id: PropTypes.number.isRequired,
  bracketId: PropTypes.number.isRequired,
  name: PropTypes.string.isRequired,
  source: PropTypes.string,
  image: PropTypes.string,
  seed: PropTypes.number,
  meta: PropTypes.object,
});

export const BracketPropType = PropTypes.shape({
  id: PropTypes.number.isRequired,
  name: PropTypes.string.isRequired,
  perma: PropTypes.string.isRequired,
  start: PropTypes.number.isRequired,
  state: PropTypes.number.isRequired,
  pic: PropTypes.string,
  winnerCharacterId: PropTypes.number,
  rules: PropTypes.string.isRequired,
  source: PropTypes.string.isRequired,
  advanceHour: PropTypes.number,
  nameLabel: PropTypes.string.isRequired,
  sourceLabel: PropTypes.string,
  score: PropTypes.number,
  externalId: PropTypes.string,
  minAge: PropTypes.number,
  hidden: PropTypes.bool,
  blurb: PropTypes.string,
  captcha: PropTypes.bool,
});
