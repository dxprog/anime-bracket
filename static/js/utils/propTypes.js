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
