import Molecule from 'molecule';

if (!window._singletons) {
  window._singletons = {};
}

export default function Singleton(name, classDefinition) {
  let retVal = window._singletons[name];

  if (!retVal && typeof classDefinition === 'object') {
    retVal = window._singletons[name] = new (Molecule(classDefinition));
  }

  return retVal;
};