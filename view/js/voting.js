(function($) {

    var

    $votes = [],
    bracketId = window.bracketId,

    voteCallback = function(data) {

        var message = '';

        if (data.success) {
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

    submitClick = function(e) {
        var voteData = '';

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
                data:{ bracketId:window.bracketId, votes:voteData},
                success:voteCallback
            });
        }

    },

    init = (function() {
        $('.entrant').on('click', entrantClick);
        $('button').on('click', submitClick);
    }());

}(jQuery));