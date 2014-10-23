(function($) {

    var

        $message = $('.message'),
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
                }
            }).fail(function() {
                setMessage('There was an unexpected error talking to the server. Please try again in a few moments.', false);
            });

        };

    if ($('#page-vote').length) {
        // For deselected an already selected radio
        $('form').on('click', '[type="radio"]:not([disabled]) + label', deselectEntrant);
        $('form').on('submit', formSubmit);
    }

}(jQuery));