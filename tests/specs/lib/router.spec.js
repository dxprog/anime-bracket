import { expect } from 'chai';
import sinon from 'sinon';

import Router from 'lib/router';

const NOOP = function() {};

describe('Router tests', function() {

  let sandbox;

  beforeEach(function() {
    sandbox = sinon.sandbox.create();
  });

  afterEach(function() {
    sandbox.restore();
  });

  it('should exist as an object', function() {
    expect(Router).to.be.an('object');
  });

  describe('Route adding', function() {

    it('should add the route to the map and register the callback', function() {
      const routeName = 'this.route';
      const routePath = '/this/:route';
      Router.addRoute(routeName, routePath, NOOP);
      expect(Router._routes).to.have.property(routeName);
      const route = Router._routes[routeName];
      expect(route.path).to.equal(routePath);
      expect(route.callbacks).to.have.length(1);
      expect(route.callbacks[0]).to.equal(NOOP);
    });

    it('should generate a name based on the route and add the route when adding multiple routes', function() {
      sandbox.spy(Router, 'addRoute');
      Router.addRoutes({
        '/this/:route': NOOP,
        '/this/other/:route': NOOP
      });

      sinon.assert.calledTwice(Router.addRoute);
      sinon.assert.calledWith(Router.addRoute.firstCall, 'this.route', '/this/:route');
      sinon.assert.calledWith(Router.addRoute.secondCall, 'this.other.route', '/this/other/:route');
    });

  });

  describe('Route compilation', function() {
    it('should handle simple paths', function() {
      const path = Router._compilePath('/simple/path');
      expect(path.regExStr).to.equal('^\\/simple\\/path$');
    });

    it('should handle parameters', function() {
      const path = Router._compilePath('/simple/:param1/:param2');
      expect(path.regExStr).to.equal('^\\/simple\\/([^\\/]+)\\/([^\\/]+)$');
      expect(path.map['1']).to.equal('param1');
      expect(path.map['2']).to.equal('param2');
    });

    it('should handle wildcards', function() {
      const path = Router._compilePath('/simple/*');
      expect(path.regExStr).to.equal('^\\/simple\\/([\\w\\/-]+)$');
    });
  });

});