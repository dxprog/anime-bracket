(function() {

    var $nav,
        CLICK = 'click',
        SHOW_NAV = 'show';

    function navClick(evt) {
        $nav.toggleClass(SHOW_NAV);
    }

    function bodyClick(evt) {
        if (!$(evt.target).closest('nav').length) {
            $nav.removeClass(SHOW_NAV);
        }
    }

    $(function() {
        $nav = $('nav');
        $nav.on(CLICK, navClick);
        $('body').on(CLICK, bodyClick);
    });

}());