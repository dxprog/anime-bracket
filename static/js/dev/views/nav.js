import $ from 'jquery';

import Singleton from 'molecule-singleton';

const CLICK = 'click';
const SHOW_NAV = 'show';

export default Singleton('nav', {
  navClick(evt) {
    this._$nav.toggleClass(SHOW_NAV);
  },

  bodyClick(evt) {
    if (!$(evt.target).closest('nav').length) {
      this._$nav.removeClass(SHOW_NAV);
    }
  },

  init() {
    this._$nav = $('nav').on(CLICK, this.navClick.bind(this));
    $('body').on(CLICK, this.bodyClick.bind(this));
  }
});