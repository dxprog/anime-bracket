import Molecule from 'molecule';

let Singleton = Molecule({
  __construct() {
    this._objects = {};
  },
  
  define(name, classDefinition) {
    let retVal = this.getByName(name);
    
    if (!retVal && typeof classDefinition === 'object') {
      retVal = this._objects[name] = new (Molecule(classDefinition));
    }
    
    return retVal;
  },
  
  getByName(name) {
    return !!this._objects[name] ? this._objects[name] : null;
  }
});

if (!window._singleton) {
  window._singleton = new Singleton();
}

export default window._singleton;