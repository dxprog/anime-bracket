import $ from 'jquery';
import Molecule from 'molecule';

import Typeahead from 'templates/typeahead.hbs';

const MAX_NUM_ITEMS = 10;
const KEYCODE_UP = 38;
const KEYCODE_DOWN = 40;
const KEYCODE_ENTER = 13;
const KEYCODE_TAB = 9;
const KEYCODES = [ KEYCODE_ENTER, KEYCODE_DOWN, KEYCODE_UP, KEYCODE_TAB ];
const SELECTED = 'selected';

export default Molecule({
  __construct($el, onSelect, bracketId) {

    this._fullCache = [];
    this._itemCache = [];
    this._currentDataset = '';
    this._loadingDataset = false;
    this._listVisible = false;
    this._itemSelected = false;
    this._onSelect = onSelect;
    this._bracketId = bracketId;

    this._$el = $el
      .on('keydown', this.handleKeyDown.bind(this))
      .on('keyup', this.handleKeypress.bind(this))
      .on('blur', this.handleBlur.bind(this));
    this._$container = $('<ul id="typeahead"></ul>')
      .hide()
      .on('click', 'li', this.handleItemClick.bind(this))
      .appendTo('body');
  },

  // Handles keyboard selection navigation
  handleKeyDown(evt) {
    const $container = this._$container;
    const keyCode = evt.keyCode || evt.charCode;
    const $currentItem = this._$container.find('.selected');

    if (KEYCODES.indexOf(keyCode) !== -1) {
      switch (keyCode) {
        case KEYCODE_UP:
          $currentItem.prev().addClass(SELECTED);
          $currentItem.removeClass(SELECTED);

          // Loop back to the bottom if off the top
          if ($container.find('.selected').length === 0) {
            $container.find('li:last').addClass(SELECTED);
          }

          break;
        case KEYCODE_DOWN:
          $container.find('.selected + li').addClass(SELECTED);
          $currentItem.removeClass(SELECTED);

          // Loop back to the top if off the bottom
          if ($container.find('.selected').length === 0) {
            $container.find('li:first').addClass(SELECTED);
          }

          break;
        case KEYCODE_ENTER:
        case KEYCODE_TAB:
          if (this._listVisible) {
            evt.preventDefault();
            this.selectItem($currentItem.attr('data-index'));
            this.hideList();
          }
          break;
      }
    }

  },

  handleKeypress(evt) {

    const $container = this._$container;
    const text = this._$el.val();
    const keyCode = evt.keyCode || evt.charCode;

    let dataset = '';
    let position = null;

    if (text.length > 1 && KEYCODES.indexOf(keyCode) === -1) {
      $container.show();
      dataset = text.substr(0, 2).toUpperCase();
      if (dataset === this._currentDataset) {
        this.findMatches();
      } else {
        if (!this._loadingDataset) {
          position = this._$el.offset();
          $container.css({ left: position.left + 'px', top: (position.top + this._$el.outerHeight()) + 'px' });

          this._loadingDataset = dataset;
          $.ajax({
            url: `/typeahead/?q=${dataset}&bracketId=${this._bracketId}`,
            dataType: 'json',
            success: this.dataCallback.bind(this)
          });
        }
      }
    } else if (text.length <= 1) {
      this.hideList();
    }

  },

  handleItemClick(evt) {
    this.selectItem(evt.currentTarget.getAttribute('data-index'));
    this._itemSelected = true;
  },

  selectItem(index) {
    if (index < this._itemCache.length) {
      this._onSelect(this._itemCache[index]);
      this.hideList();
    }
  },

  // Displays the data provided in the dropdown
  displayMatches(data) {
    if (data.length > 0) {
      this._$container
        .html(Typeahead(data))
        .show()
        .find('li:first')
        .addClass(SELECTED);
      this._listVisible = true;
    } else {
      this.hideList();
    }
  },

  findMatches() {
    const query = this._$el.val().toLowerCase();
    const out = [];

    let item = null;

    for (let i = 0, count = this._itemCache.length; i < count; i++) {
      item = this._itemCache[i];
      if (item.name.toLowerCase().indexOf(query) > -1) {
        item.index = i;
        out.push(item);
      }
    }

    // Sort the items by name
    out.sort(function(a, b) {
      var x = a.name.toLowerCase().indexOf(query),
        y = b.name.toLowerCase().indexOf(query);
      return a.order < b.order ? -1 : (x < y ? -1 : (x === y ? (a.name < b.name ? -1 : 1) : 1));
    });

    this.displayMatches(out.splice(0, MAX_NUM_ITEMS));

  },

  handleBlur(evt) {
    // NOOP for a moment to see if a click event was fired
    setTimeout(() => {
      if (!this._itemSelected) {
        this.hideList();
      }
      this._itemSelected = false;
    }, 100);
  },

  hideList() {
    this._$container.hide();
    this._listVisible = false;
  },

  dataCallback(data) {
    this._currentDataset = this._loadingDataset;
    this._loadingDataset = false;
    this._fullCache = data;
    this._itemCache = data;
    this.findMatches();
  }

});