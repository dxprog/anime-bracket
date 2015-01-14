(function() {

    var $nominate = $('#page-nominate'),
        $txtName = $('#txtName'),
        $txtSource = $('#txtSource'),
        $txtPic = $('#txtPic'),
        $form = $nominate.find('form'),
        $message = $form.find('.message'),
        bracketId = $form.find('[name="bracketId"]').val(),
        characterTypeahead = null,
        verified = false,

        isIE = (/MSIE/).test(window.navigator.userAgent),

        displayMessage = function(message, success) {
            $message.removeClass('error success hidden').html(message).addClass(success ? 'success' : 'error');
        },

        nomineeCallback = function(data) {
            displayMessage(data.success ? '"' + $txtName.val() + '" was successfully nominated!' : data.message, data.success);
            $txtName.focus().val(data.success ? '' : $txtName.val());
            $txtSource.val(data.success ? '' : $txtSource.val());
            $txtPic.val(data.success ? '' : $txtPic.val());
            verified = data.success ? false : verified;
            setFormState(true);
        },

        nomineeKeypress = function(e) {
            if ((e.keyCode == 13 || e.charCode == 13) && !isIE) {
                nomineeSubmit(null);
            } else {
                verified = false;
            }
        },

        verifyImage = function() {
            var image = new Image,
                $dfr = $.Deferred();
            image.onload = function() {
                $dfr.resolve();
            };
            image.onerror = function() {
                $dfr.reject();
            }
            image.src = $txtPic.val();
            return $dfr.promise();
        },

        setFormState = function(enabled) {
            $txtName.prop('readonly', !enabled);
            $txtSource.prop('readonly', !enabled);
            $txtPic.prop('readonly', !enabled);
            $form.find('button').prop('disabled', !enabled);
        },

        nomineeSubmit = function(e) {

            var submit = $txtName.val().length && $txtSource.val().length && $txtPic.val().length;

            if (null != e) {
                e.preventDefault();
            }

            $nominate.find('.error').removeClass('error');

            if (!submit) {
                if (!$txtName.val().length) {
                    $txtName.addClass('error');
                }
                if (!$txtSource.val().length) {
                    $txtSource.addClass('error');
                }
                if (!$txtPic.val().length) {
                    $txtPic.addClass('error');
                }
            } else {
                setFormState(false);
                // Verified characters (one that has been nominated or added to the bracket already) get NOOPs
                if (!verified) {
                    verifyImage().done(function() {
                        $.ajax({
                            url:'/submit/?action=nominate',
                            dataType:'json',
                            type:'POST',
                            data: $form.serialize(),
                            success:nomineeCallback
                        });
                    }).fail(function() {
                        displayMessage('Invalid picture', false);
                        $txtPic.addClass('error');
                        setFormState(true);
                    });

                } else {
                    nomineeCallback({ success: true });
                }

            }
        },

        formShow = function(e) {
            $nominate.find('.info').hide();
            $nominate.find('.form').show();
            $txtName.focus();
            e.preventDefault();
        },

        characterChosen = function(data) {
            if (null !== data) {
                $txtName.val(data.name);
                $txtSource.val(data.source);
                $txtPic.val(data.image).focus();
                verified = data.verified;
            }
        };

    if ($nominate.length) {
        $nominate
            .on('click', '.accept', formShow)
            .on('click', 'button[type="submit"]', nomineeSubmit)
            .on('keypress', 'input', nomineeKeypress);
        characterTypeahead = new Typeahead($txtName, characterChosen, bracketId);
    }

}());