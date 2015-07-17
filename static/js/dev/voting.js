(function($) {

    // Scoping all this off has to be the dumbest and laziest thing I've ever done for perf gains
    function PageVote() {

        var $message = $('.message'),
            $votesCode = $('.votes-code'),
            $overlay = $votesCode.find('.overlay'),
            $markdown = $('#votes_markdown'),
            bracketId = window.bracketId,

            deselectEntrant = function(evt) {
                var radio = document.getElementById(evt.currentTarget.getAttribute('for'));
                if (radio.checked) {
                    radio.checked = false;
                    evt.preventDefault();
                }
            },

            setMessage = function(message, success) {
                $(window).scrollTop(0);
                $message.html(message).removeClass('hidden error success').addClass(success ? 'success' : 'error')
            },

            showMarkdownModal = function(evt) {
                var markdown = '';

                $('.voting li').each(function() {
                    var $this = $(this),
                        voted = !!$this.find('input:checked').length,
                        name = $this.find('dd.name').text(),
                        img = $this.find('img').attr('src'),
                        isFirst = $this.hasClass('entrant1');

                    // This might actually be the most disgusting single line of code I've ever written... I'm so proud of myself!
                    markdown += ' - [' + (voted ? '**' : '~~') + name + (voted ? '**' : '~~') + '](' + img + ')' + (isFirst ? '' : '\n');
                });

                $markdown.val(markdown);
                $overlay.fadeIn(function() {
                    $markdown.focus();
                });
            },

            hideMarkdownModal = function(evt) {
                if (evt.target.tagName !== 'TEXTAREA') {
                    $overlay.fadeOut();
                }
            },

            formSubmit = function(evt) {
                var $this = $(evt.currentTarget);
                evt.preventDefault();

                $.ajax({
                    url: $this.attr('action'),
                    data: $this.serialize(),
                    dataType: 'json',
                    type: 'POST'
                }).done(function(data) {
                    setMessage(data.message, data.success);

                    if (data.success) {
                        // Disable all the rounds the user voted on
                        $('input:checked').each(function() {
                            var name = this.getAttribute('name');
                            $('[name="' + name + '"]').prop('disabled', true);
                        });

                        $votesCode.show();
                    }
                }).fail(function() {
                    setMessage('There was an unexpected error talking to the server. Please try again in a few moments.', false);
                });

            };

        // For deselected an already selected radio
        $('#vote-form')
            .on('click', '[type="radio"]:not([disabled]) + label', deselectEntrant)
            .on('submit', formSubmit);

        $votesCode.on('click', 'button', showMarkdownModal);
        $overlay.on('click', hideMarkdownModal);

    }

    if ($('#page-vote').length) {
        PageVote();
    }

}(jQuery));