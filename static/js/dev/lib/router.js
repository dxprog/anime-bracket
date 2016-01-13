import _ from 'underscore';

import Singleton from './singleton';

const PATH_DELIMITER = '/';
const PARAM_MARKER = ':';
const QS_MARKER = '?';
const MODE_STANDARD = 0;
const MODE_PARAM = 1;


export default Singleton('router', {

  __construct() {
    this._routes = {};
    this._supportsHistory = 'history' in window;
  },

  /**
   * Bulk add routes. Name will be determined by path, eg: /this/page/thing/:id -> this.page.thing.id
   */
  addRoutes(routes) {
    Object.keys(routes).forEach((path) => {
      let name = path.replace(/\//g, '.').replace(/(^\.|:|\.$)/g, '');
      this.addRoute(name, path, routes[path]);
    });
  },

  addRoute(name, path, callback) {
    this._routes[name] = {
        path: path,
        callbacks: []
    };

    _.defaults(this._routes[name], this._compilePath(path));

    if (typeof callback === 'function') {
        this._routes[name].callbacks.push(callback);
    }
  },

  on(route, callback) {
    if (route in this._routes) {
        this._routes[route].callbacks.push(callback);
    }
  },

  go(route, params) {

    var url,
        oldUrl,
        qs = [];

    // Look for the route by name in the list
    if (!!this._routes[route]) {

        url = this._routes[route].path;
        oldUrl = url;

        _.each(this._routes[route].callbacks, function(callback) {
            var addParams = callback(params);

            // If the callback returned additional parameters, add them to the list
            // so that all values are correctly represented in the final URL
            if (typeof addParams === 'object') {
                _.defaults(params, addParams);
            }
        });

        if (this._supportsHistory) {
            _.each(params, function(value, key) {
                url = url.replace(new RegExp('(\\*|\\:)' + key), value);

                // If the parameter wasn't found as part of the route, throw it on the query string
                if (oldUrl === url) {
                    qs.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                }

                oldUrl = url;
            });

            url = qs.length ? url + '?' + qs.join('&') : url;

            // Don't push the index page
            if (url !== 'index') {
              window.history.pushState({
                  route: route,
                  params: params
              }, null, url);
            }
        }

    } else {

        // Otherwise, match against a path
        route = this._getRouteFromPath(route);

    }
  },

  _getRouteFromPath(path) {

    var i, result, route,
        params = {};

    // Root gets renamed to index
    path = path === '/' ? 'index' : path;

    for (var i in this._routes) {
        if (this._routes.hasOwnProperty(i)) {
            route = this._routes[i];
            result = route.regEx.exec(path);

            if (result) {
                _.each(route.map, function(name, index) {
                    params[name] = result[index];
                });

                this.go(i, params);
                return;
            }
        }
    }
  },

  /**
   * Generates a regular expression so that a path can be tracked back to its route
   */
  _compilePath(path) {
    var regEx = '^',
        paramName = '',
        mode = MODE_STANDARD,
        i = 0,
        count = path.length,
        character,
        paramCount = 1,
        paramMap = {},
        hasQueryString = false;

    for (; i < count; i++) {

        // If this route has already parsed a querystring placeholder, throw an error
        // because that must be the last part of a route if it's present
        if (hasQueryString) {
            throw 'Cannot have additional path/parameters after a query string marker';
            return;
        }

        character = path.charAt(i);

        if (MODE_PARAM === mode) {
            // A parameter marker in a character is invalid
            if (PARAM_MARKER === character) {
                throw 'Invalid character in route path';
                return;
            } else if (PATH_DELIMITER === character) {
                regEx = regEx.concat('([^\\/]+)\\/');
                paramMap[paramCount] = paramName;
                paramName = '';
                mode = MODE_STANDARD;
                paramCount++;
            } else {
                paramName = paramName.concat(character);
            }
        } else {
            if (PARAM_MARKER === character) {
                mode = MODE_PARAM;
            } else if (QS_MARKER === character) {
                hasQueryString = true;
                regEx = regEx.concat('([\w]+)');
            } else {
                regEx = regEx.concat(character);
            }
        }
    }

    // If the route ended on a parameter, handle it
    if (paramName.length > 0) {
        regEx = regEx.concat('([^\\/]+)');
        paramMap[paramCount] = paramName;
    }

    regEx = new RegExp(regEx.concat('$'), 'ig');

    return {
        regEx: regEx,
        map: paramMap
    };
  }

});