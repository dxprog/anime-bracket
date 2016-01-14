import Molecule from 'molecule';

import Singleton from './singleton';

let Route = Molecule({
  initRoute() {},
  revisitRoute() {}
});

export default function(name, classDefinition) {
  let routeInstance = Singleton(name, Route.extend(classDefinition));
  if (!routeInstance.__initialized) {
    try {
      routeInstance.initRoute();
      routeInstance.__initialized = true;
    } catch (exc) {
      throw `Unable to initialize route "${name}": ${exc.message}`;
    }
  } else {
    routeInstance.revisitRoute();
  }
}