/* globals describe,it,beforeEach,afterEach */
import { expect } from 'chai';
import sinon from 'sinon';

import Route from '../../../static/js/dev/lib/route';
import Singleton from '../../../static/js/dev/lib/singleton';

function getRouteInstance(name) {
  return Singleton(`__ROUTE_${name}`);
}

describe('Route tests', function() {

  let sandbox;

  beforeEach(function() {
    sandbox = sinon.sandbox.create();
  });

  afterEach(function() {
    sandbox.restore();
  });

  it('should instantiate as a Route and register a singleton', function() {
    const routeName = 'this-is-route';
    Route(routeName, {});
    const routeInstance = getRouteInstance(routeName);
    expect(routeInstance).to.be.an('object');
    expect(routeInstance.initRoute).to.be.a('function');
    expect(routeInstance.revisitRoute).to.be.a('function');
  });

  it('should not invoke initRoute/revisitRoute on creation or refetch', function() {
    const routeName = 'another-route';
    const routeInvoke = Route(routeName, {
      initRoute: sandbox.spy()
    });
    const routeInstance = getRouteInstance(routeName);

    expect(routeInvoke).to.be.a('function');
    sinon.assert.notCalled(routeInstance.initRoute);

    // Try re-getting
    Route(routeName);
    sinon.assert.notCalled(routeInstance.initRoute);
  });

  it('should init a route via the returned invoker only on the first get', function() {
    const routeName = 'new-route';
    const routeInvoke = Route(routeName, {
      initRoute: sandbox.spy(),
      revisitRoute: sandbox.spy()
    });
    const routeInstance = getRouteInstance(routeName);

    routeInvoke();
    sinon.assert.calledOnce(routeInstance.initRoute);
    sinon.assert.notCalled(routeInstance.revisitRoute);

    routeInvoke();
    sinon.assert.calledOnce(routeInstance.initRoute);
    sinon.assert.calledOnce(routeInstance.revisitRoute);
  });
});