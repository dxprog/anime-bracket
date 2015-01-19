(function(undefined) {

    $(function() {

        // Set the UTC cookie
        if (document.cookie.indexOf('utcOffset') === -1) {
            // 1000 days in the future...
            var expires = new Date();
            expires.setTime(Date.now() + (86400 * 1000 * 1000));
            document.cookie = 'utcOffset=' + (new Date).getTimezoneOffset() + '; expires=' + expires.toGMTString() + '; path=/';
        }

    });

}());