(function($, undefined) {
    
    var infoCache = {},

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
        }
    };

    window.CharacterInfo = CharacterInfo;

}(jQuery));