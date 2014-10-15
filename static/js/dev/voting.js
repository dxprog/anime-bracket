(function($) {

    var

        $message = $('.message'),
        bracketId = window.bracketId,

        setMessage = function(message, success) {
            $(window).scrollTop(0);
            $message.html(message).removeClass('hidden error success').addClass(success ? 'success' : 'error')
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
                    // Disable all the characters the user voted for
                    $('input:checked').prop('disabled', true);
                }
            }).fail(function() {
                setMessage('There was an unexpected error talking to the server. Please try again in a few moments.', false);
            });

        };

    if ($('#page-vote').length) {
        $('form').on('submit', formSubmit);
    }

}(jQuery));