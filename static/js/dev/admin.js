(function(undefined) {
    
    'use strict';

    var 

        /**
         * Multiple script loader
         * @param {Array} scripts Array of scripts to load
         * @return {Promise} jQuery promise that is resolved when all scripts have been loaded
         */
        loadScripts = function(scripts) {
            var $dfr = $.Deferred(),

                scriptLoaded = function() {
                    if (scripts.length > 0) {
                        $.getScript(scripts.shift()).done(scriptLoaded);
                    } else {
                        $dfr.resolve();
                    }
                };

            $.getScript(scripts.shift()).done(scriptLoaded);

            return $dfr.promise();
        },

        initNomineeForm = function() {
            loadScripts([ '/static/js/jquery.Jcrop.min.js', '/static/js/nominee.js' ]);
        };

    loadScripts([ '/static/js/adminTemplates.js' ]);

    if ($('body#admin .nominee').length) {
        initNomineeForm();
    }

}());