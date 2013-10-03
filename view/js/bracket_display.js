(function(undefined) {
   
    var COLUMN_WIDTH = 298,
        tiers = [],
        i = 0,
        count = bracketData.results.length,
        $content = $('#content'),
        $body = $('body'),
        $header = $('header'),
        wildCardRound = 0,
        tier = null,
        lastEntrantCount = 9999,
        groups = 0,

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
                max = tier < wildCardRound ? wildCardRound : count,
                lastRound = null,
                bracketHeight = 0,
                winner = {};

            tier = tier || 0;
            bracketHeight = Math.pow(2, max - tier) * 100 / 2;

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
                left += Templates.winner(winner);
            }

            // Add an additional column for the winner
            columns++;
            width = columns * COLUMN_WIDTH;

            $body.width(width);
            $content.width(width).html(left + right);
            
        },

        handleGroupChange = function(e) {
            var $target = $(e.currentTarget),
                group = $target.attr('data-group'),
                tier = null;

            $header.find('.selected').removeClass('selected');
            $target.addClass('selected');

            if (group === 'finals') {
                group = null;
                tier = wildCardRound + 1;
            } else {
                group = parseInt(group, 10);
            }
            renderBracket(group, tier);

            if (typeof window.history.pushState === 'function') {
                history.pushState(null, window.title, '/' + window.bracketData.perma + '/view/?group=' + (group + 1));
            }

        },

        populateGroups = function() {
            var out = [],
                i = 0;

            for (; i < groups + 1; i++) {
                out.push({ name:String.fromCharCode(i + 65), index:i });
            }

            $header
                .find('ul')
                .prepend(Templates.groupPicker({ groups:out }))
                .on('click', 'li', handleGroupChange)
                .find('[data-group="' + (group - 1) + '"]')
                .addClass('selected');
        },

        qs = parseQueryString(),
        group = qs.hasOwnProperty('group') ? parseInt(qs.group, 10) : 1;

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
        if (tier.entrants > lastEntrantCount) {
            wildCardRound = i;
        }
        lastEntrantCount = tier.entrants;
        tiers.push(tier);
    }

    renderBracket(group - 1, null);

    $body.on('mouseover', '.entrant', function(e) {
        var id = e.currentTarget.getAttribute('data-id');
        if ('1' !== id) {
            $('.highlighted').removeClass('highlighted');
            $('.entrant[data-id="' + e.currentTarget.getAttribute('data-id') + '"]')
                .addClass('highlighted')
                .parent().addClass('highlighted');
        }
    });

    populateGroups();
    $header.find('.title').text(window.bracketData.name);

}());