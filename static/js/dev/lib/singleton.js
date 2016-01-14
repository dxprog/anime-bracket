import Molecule from 'molecule';

if (!window._singletons) {
  window._singletons = {};
}

export default function Singleton(name, classDefinition) {
  let retVal = window._singletons[name];

  if (!retVal && (typeof classDefinition === 'object' || classDefinition._isMolecule)) {
    // If the passed definition is a Molecule already, just instantiate that
    // Otherwise, create a new one
    if (classDefinition._isMolecule) {
      retVal = window._singletons[name] = new classDefinition();
    } else {
      retVal = window._singletons[name] = new (Molecule(classDefinition));
    }
  }

  return retVal;
};