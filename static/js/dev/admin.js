(function(undefined) {

    'use strict';

    var $admin = $('body#admin'),

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

        initStatsPage = function() {
            loadScripts([ '/static/js/Chart.min.js', '/static/js/stats.js' ]);
        },

        initStartBracketForm = function() {
            loadScripts([ '/static/js/start-bracket.js' ]);
        },

        _updateCharacter = function(evt) {
            var $target = $(evt.currentTarget),
                $parent = $target.closest('tr'),
                $table = $parent.closest('table'),
                perma = $table.data('bracket'),
                type = $table.data('type'),
                payload = null;

            $parent.addClass('loading');

            if ('nominee' === type) {
                payload = {
                    id: $parent.data('id'),
                    ignore: 'true'
                };
            } else if ('character' === type) {
                payload = {
                    name: $parent.find('[name="name"]').val(),
                    source: $parent.find('[name="source"]').val(),
                    characterId: $parent.data('id'),
                    action: $target.val()
                };
            }

            if (payload) {
                $.ajax({
                    url: '/me/process/' + $table.data('bracket') + '/' + type + '/',
                    type: 'POST',
                    dataType: 'json',
                    data: payload
                }).done(function(data) {
                    if (data.success) {
                        $parent.removeClass('loading').addClass('success');
                        if (data.action === 'delete' || 'nominee' === type) {
                            $parent.remove();
                        }
                    } else {
                        $parent.removeClass('loading').addClass('failed');
                    }

                });
            }
        },

        deleteConfirmation = function(evt) {
            var retVal = window.confirm('All nominee, characters, and votes will be deleted. Do you wish to continue?');
            if (!retVal) {
                evt.preventDefault();
            }
            return retVal;
        },

        initCharactersForm = function() {
            $('.characters').on('click', 'button', _updateCharacter);
        };

    if ($admin.length) {
        loadScripts([ '/static/js/adminTemplates.js' ]);

        if ($admin.find('.nominee').length) {
            initNomineeForm();
        } else if ($admin.find('.characters').length) {
            initCharactersForm();
        } else if ($admin.find('.stats').length) {
            initStatsPage();
        } else if ($admin.find('.start-bracket').length) {
            initStartBracketForm();
        } else {
            $('.bracket-card .delete').on('click', deleteConfirmation);
        }
    }

}());