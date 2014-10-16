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
        },

        _updateCharacter = function(evt) {
            var $target = $(evt.currentTarget),
                $parent = $target.closest('tr'),
                $table = $parent.closest('table');

            $parent.addClass('loading');

            $.ajax({
                url: '/admin/' + $table.data('bracket') + '/process/character/',
                type: 'POST',
                dataType: 'json',
                data: {
                    name: $parent.find('[name="name"]').val(),
                    source: $parent.find('[name="source"]').val(),
                    characterId: $parent.data('id'),
                    action: $target.val()
                }
            }).done(function(data) {
                if (data.success) {
                    $parent.removeClass('loading').addClass('success');
                } else {
                    $parent.removeClass('loading').addClass('failed');
                }
            });

        },

        initCharactersForm = function() {
            $('.characters').on('click', 'button', _updateCharacter);
        };

    loadScripts([ '/static/js/adminTemplates.js' ]);

    if ($('body#admin .nominee').length) {
        initNomineeForm();
    } else if ($('#admin .characters').length) {
        initCharactersForm();
    }

}());