(function(undefined) {

    var COLUMN_WIDTH = 298,
        tiers = [],
        i = 0,
        count = null,
        $content = $('.bracket-display'),
        $body = $('body'),
        $header = $('header'),
        tier = null,
        lastEntrantCount = 9999,
        groups = 0,
        popup = null,

        parseQueryString = function(qs) {

            var
                retVal = {},
                i = null,
                count = 0,
                kvp = null;

            if (!qs) {
                qs = location.href.indexOf('?') !== -1 ? location.href.split('?')[1] : null;
            }

            if (qs) {
                qs = qs.split('&');
                for (i = 0, count = qs.length; i < count; i++) {
                    kvp = qs[i].split('=');
                    retVal[kvp[0]] = kvp.length === 1 ? true : decodeURIComponent(kvp[1]);
                }
            }

            return retVal;

        },

        renderBracket = function(group, tier) {
            var left = '',
                right = '',
                temp = [],
                columns = 0,
                max = tiersForGroup(group),
                lastRound = null,
                bracketHeight = 0,
                visibleTiers = 0,
                winner = {};

            tier = tier || 0;
            bracketHeight = Math.pow(2, max - tier - 1) * 100;

            for (i = tier; i < max; i++) {
                temp = tiers[i].render(i - tier, group, true);
                left += temp[0];
                right = temp[1] + right;
                columns += 2;
            }

            // Render the winner
            lastRound = tiers[i - 1].getRound(0, group);
            if (null !== lastRound && null !== lastRound.entrant1 && null != lastRound.entrant2) {
                if (!lastRound.entrant1.votes && !lastRound.entrant2.votes) {
                    winner = { entrant:new Entrant(null, 0) };
                } else {
                    if (lastRound.entrant1.votes > lastRound.entrant2.votes) {
                        winner = { entrant: lastRound.entrant1 };
                    } else {
                        winner = { entrant: lastRound.entrant2 };
                    }
                }
                winner.height = bracketHeight;
                left += Templates['views/winner'](winner);
            }

            // Add an additional column for the winner
            columns++;
            width = columns * COLUMN_WIDTH;

            $body.width(width);
            $content.width(width).html(left + right);

        },

        /**
         * Returns the number of tiers that will be in a group
         */
        tiersForGroup = function(group, displayFinalRound) {
            var rounds = tiers[0].getRoundsForGroup(group).length;
            return Math.log(rounds) / Math.LN2 + 1;
        },

        handleGroupChange = function(e) {
            changeGroup($(e.currentTarget).data('group'));
        },

        changeGroup = function(group, ignoreHistory) {
            var tier = null,
                urlGroup = group,
                displayFinalRound = false;

            $header.find('.selected').removeClass('selected');
            $header.find('[data-group="' + group + '"]').addClass('selected');

            if (group === 'finals') {
                group = null;
                displayFinalRound = true;
                tier = count - 3;
            } else if (group === 'full') {
                group = null;
                displayFinalRound = true;
                tier = 0;
            } else {
                group = parseInt(group, 10);
                urlGroup = group + 1;
            }
            renderBracket(group, tier, displayFinalRound);

            if (typeof window.history.pushState === 'function' && !ignoreHistory) {
                history.pushState(null, window.title, '/results/' + window.bracketData.perma + '/?group=' + urlGroup);
            }

        },

        hidePopups = function() {
            popup = null;
            $('.stats-popup').remove();
        },

        handleMouseOver = function(evt) {
            var id = evt.currentTarget.getAttribute('data-id');
            if ('1' !== id) {
                if (popup) {
                    hidePopups();
                }
                // popup = new CharacterInfo($(evt.currentTarget).parent(), id);
                $('.highlighted').removeClass('highlighted');
                $('.entrant[data-id="' + id + '"]')
                    .addClass('highlighted')
                    .parent().addClass('highlighted');
            }
        },

        handleMouseOut = function(evt) {
            hidePopups();
        },

        populateGroups = function() {
            var out = [],
                i = 0;


            for (; i < groups; i++) {
                out.push({ name:String.fromCharCode(i + 65), index:i });
            }

            $header
                .find('ul.groups')
                .prepend(Templates['views/groupPicker']({ groups:out }))
                .on('click', 'li', handleGroupChange);
        },

        qs = parseQueryString(),
        group = qs.hasOwnProperty('group') ? qs.group : 1;

    if (window.hasOwnProperty('bracketData')) {

        count = bracketData.results.length;

        Handlebars.registerHelper('userVoted', function(entrant, options) {
            var retVal = '',
                id = '' + this.id;
            if (window.bracketData.userVotes && bracketData.userVotes.hasOwnProperty(id)) {
                retVal = bracketData.userVotes[id] == entrant.id ? options.fn(this) : '';
            }
            return retVal;
        });

        for (; i < count; i++) {
            tier = new Tier(bracketData.results[i]);
            groups = tier.groups > groups ? tier.groups : groups;
            lastEntrantCount = tier.entrants;
            tiers.push(tier);
        }

        // Increment by 1 because group IDs are 0 based
        groups = groups + 1;

        $body
            .on('mouseover', '.entrant-info', handleMouseOver)
            .on('mouseout', '.entrant-info', handleMouseOut);

        $header.find('.title').text(window.bracketData.name);

        group = isNaN(group) ? group : group - 1;
        populateGroups();
        changeGroup(group, true);

    }

}());