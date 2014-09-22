(function($, undefined) {
    
    var infoCache = {},

        VERTICAL_PADDING = 50,

        CharacterInfo = function($el, id) {

            var self = this,
                ajaxCallback = function(data) {
                    infoCache[id] = data;
                    self._data = data;
                    self.show($el);
                };

            if (!infoCache.hasOwnProperty(id)) {
                $.ajax({
                    url: '/stats/?action=character&id=' + id,
                    dataType: 'jsonp',
                    success: ajaxCallback
                });
            } else {
                this._data = infoCache[id];
                this.show($el);
            }

        };

    CharacterInfo.prototype.hide = function() {
        if (this._$popup instanceof $) {
            this._$popup.remove();
            this._$popup = null;
        }
    };

    CharacterInfo.prototype.show = function($el) {
        if (!(this._$popup instanceof $)) {
            this._$popup = $(Templates.statsPopup(this._data));
            this._$popup.appendTo($el);
            
            // Make sure the dialog doesn't go off the screen
            var position = this._$popup.offset(),
                height = 276,
                docHeight = $(document).height();

            if (position.top < VERTICAL_PADDING) {
                this._$popup.addClass('out-bounds-top');
            } else if (position.top + height > docHeight) {
                this._$popup.addClass('out-bounds-bottom');
            }
        }
    };

    window.CharacterInfo = CharacterInfo;

}(jQuery));