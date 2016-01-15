/* globals describe,it,beforeEach,afterEach */
import { expect } from 'chai';
import sinon from 'sinon';

import Route from '../../../static/js/dev/lib/route';
import Singleton from '../../../static/js/dev/lib/singleton';

describe('Route tests', function() {

  let sandbox;

  beforeEach(function() {
    sandbox = sinon.sandbox.create();
  });

  afterEach(function() {
    sandbox.restore();
  });

  it('should instantiate as a Route and register a singleton', function() {
    const routeName = 'this-route';
    Route(routeName, {});
    let route = Singleton(routeName);
    expect(route).to.be.an('object');
    expect(route.initRoute).to.be.a('function');
    expect(route.revisitRoute).to.be.a('function');
  });

  it('should init a route only on the first get', function() {
    const routeName = 'new-route';
    Route(routeName, {
      initRoute: sandbox.spy(),
      revisitRoute: sandbox.spy()
    });

    let route = Singleton(routeName);
    sinon.assert.calledOnce(route.initRoute);
    sinon.assert.notCalled(route.revisitRoute);

    Route(routeName);
    sinon.assert.calledOnce(route.initRoute);
    sinon.assert.calledOnce(route.revisitRoute);
  });
});