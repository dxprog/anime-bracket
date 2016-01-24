import Molecule from 'molecule';

import Singleton from './singleton';

const ROUTE_NAMESPACE = '__ROUTE_';

let _RouteClass = Molecule({
  initRoute() {},
  revisitRoute() {}
});

export default function Route(name, classDefinition) {
  let routeInstance = Singleton(`${ROUTE_NAMESPACE}${name}`, _RouteClass.extend(classDefinition));
  return function() {
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
}