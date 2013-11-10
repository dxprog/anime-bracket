(function($) {

    var

    $votes = [],
    bracketId = window.bracketId,

    voteCallback = function(data) {

        var message = '';

        if (data.success) {
            $(window).scrollTop(0);
            $('#round .rounds').remove();
            $('#round .vote-success').fadeIn();
        }

    },

    entrantClick = function(e) {
        var
            $this = $(e.currentTarget),
            $parent = $this.parent();

        if (e.target.tagName !== 'A' && !$parent.hasClass('voted')) {
            $('.wildcard .selected').removeClass('selected');

            if (!$this.hasClass('selected')) {
                $parent.find('.selected').removeClass('selected');
                $this.addClass('selected');
            } else {
                $this.removeClass('selected');
            }
        }
    },

    formatEntrant = function($entrant) {
        var name = $entrant.find('h4').text();
        return $entrant.hasClass('selected') ? '**' + name + '**' : name;
    },

    formatVotesClick = function(evt) {
        var votesWindow = window.open('', '', 'width=400,height=300'),
            doc = votesWindow.document.open(),
            text = [];

        $('.round').each(function() {
            var $this = $(this),
                $entrant1 = $this.find('.entrant.left'),
                $entrant2 = $this.find('.entrant.right');
            text.push(formatEntrant($entrant1) + ' - ' + formatEntrant($entrant2));
        });

        doc.write('Here\'s your reddit formatted votes:<br /><textarea style="width:380px; height: 350px;">' + text.join('\n') + '</textarea>');
        doc.close();

    },

    submitClick = function(e) {
        var voteData = '',
            prizes = $('#prizes').is(':checked');

        $('.round').each(function() {
            var
                $this = $(this),
                $selected = $this.find('.entrant.selected:not(.voted)');

            if ($selected.length === 1) {
                voteData += ',' + $this.attr('data-id') + ',' + $selected.attr('data-id');
                $votes.push($this);
            }
        });

        if (voteData.length > 0) {
            voteData = voteData.substr(1);
            $.ajax({
                url:'/process.php?action=vote',
                type:'POST',
                dataType:'json',
                data:{ bracketId:window.bracketId, votes:voteData, prizes:prizes },
                success:voteCallback
            });
        }

    },

    init = (function() {
        $('.entrant').on('click', entrantClick);
        $('#vote').on('click', submitClick);
        $('#formatVotes').on('click', formatVotesClick);
    }());

}(jQuery));