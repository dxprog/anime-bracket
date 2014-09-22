(function($, undefined) {

    var Typeahead = function($el, onSelect) {

        var MAX_NUM_ITEMS = 10,
            KEYCODE_UP = 38,
            KEYCODE_DOWN = 40,
            KEYCODE_ENTER = 13,
            KEYCODE_TAB = 9,
            KEYCODES = [ KEYCODE_ENTER, KEYCODE_DOWN, KEYCODE_UP, KEYCODE_TAB ],
            SELECTED = 'selected',

            fullCache = [],
            itemCache = [],
            currentDataset = '',
            loadingDataset = false,
            listVisible = false,

            $container = null,

            // Handles keyboard selection navigation
            handleKeyDown = function(evt) {

                var keyCode = evt.keyCode || evt.charCode,
                    $currentItem = $container.find('.selected');

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
                            if (listVisible) {
                                evt.preventDefault();
                                selectItem($currentItem.attr('data-index'));
                                hideList();
                            }
                            break;
                    }
                }

            },

            handleKeypress = function(evt) {

                var text = $el.val(),
                    dataset = '',
                    keyCode = evt.keyCode || evt.charCode,
                    position = null;

                if (text.length > 1 && KEYCODES.indexOf(keyCode) === -1) {
                    $container.show();
                    dataset = text.substr(0, 2).toUpperCase();
                    if (dataset === currentDataset) {
                        findMatches();
                    } else {
                        if (!loadingDataset) {
                            position = $el.offset();
                            $container.css({ left: position.left + 'px', top: (position.top + $el.outerHeight(true)) + 'px' });

                            loadingDataset = dataset;
                            $.ajax({
                                url: '/typeahead/?q=' + dataset,
                                dataType: 'json',
                                success: dataCallback
                            });
                        }
                    }

                } else if (text.length <= 1) {
                    hideList();
                }

            },

            handleItemClick = function(evt) {
                selectItem(evt.currentTarget.getAttribute('data-index'));
            },

            selectItem = function(index) {
                if (index < itemCache.length) {
                    onSelect(itemCache[index]);
                    hideList();
                }
            },

            // Displays the data provided in the dropdown
            displayMatches = function(data) {
                if (data.length > 0) {
                    $container
                        .html(Templates.typeahead(data))
                        .show()
                        .find('li:first')
                        .addClass(SELECTED);
                    listVisible = true;
                } else {
                    hideList();
                }
            },

            findMatches = function() {
                var query = $el.val().toLowerCase(),
                    i = 0,
                    count = itemCache.length,
                    out = [],
                    item = null;

                for (; i < count; i++) {
                    item = itemCache[i];
                    if (item.name.toLowerCase().indexOf(query) > -1) {
                        item.index = i;
                        out.push(item);
                    }
                }

                // Sort the items by name
                out.sort(function(a, b) {
                    var x = a.name.toLowerCase().indexOf(query),
                        y = b.name.toLowerCase().indexOf(query);
                    return x < y ? -1 : x === y ? a.name < b.name ? -1 : 1 : 1;
                });

                displayMatches(out.splice(0, MAX_NUM_ITEMS));

            },

            hideList = function() {
                $container.hide();
                listVisible = false;
            }

            dataCallback = function(data) {
                currentDataset = loadingDataset;
                loadingDataset = false;
                fullCache = data;
                itemCache = data;
                findMatches();
            },

            init = function() {
                $el
                    .on('keydown', handleKeyDown)
                    .on('keyup', handleKeypress)
                    .on('blur', hideList);
                $container = $('<ul id="typeahead"></ul>')
                    .hide()
                    .on('click', 'li', handleItemClick)
                    .appendTo('body');
            };

        if ($el instanceof $) {
            $(init);
        }

    };

    window.Typeahead = Typeahead;

}(jQuery));