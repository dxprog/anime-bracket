(function() {

    var $nominate = $('#nominate'),
        $txtName = $('#txtName'),
        $txtSource = $('#txtSource'),
        $txtPic = $('#txtPic'),
        $message = $('#message'),
        bracketId = window.bracketId,
        characterTypeahead = null,

        isIE = (/MSIE/).test(window.navigator.userAgent),

        displayMessage = function(message) {
            $message
                .stop()
                .css({ top:0, opacity:0 })
                .html(message)
                .animate({ top:'-68px', opacity:1 }, 400, function() {
                    setTimeout(function() { $message.animate({ opacity:0 }); }, 1000);
                });
        },

        nomineeCallback = function(data) {
            displayMessage(data.success ? 'Success!' : data.message);
            $txtName.focus().val(data.success ? '' : $txtName.val());
            $txtPic.val(data.success ? '' : $txtPic.val());
        },

        nomineeKeypress = function(e) {
            if ((e.keyCode == 13 || e.charCode == 13) && !isIE) {
                nomineeSubmit(null);
            }
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
                $.ajax({
                    url:'/process.php?action=nominate',
                    dataType:'json',
                    type:'POST',
                    data:{ bracketId:bracketId, nomineeName:$txtName.val(), nomineeSource:$txtSource.val(), image:$txtPic.val() },
                    success:nomineeCallback
                });
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
                $txtSource.val(data.sources[0].name);
                $txtPic.val(data.pic.replace('t.jpg', '.jpg'));
            }
        };

    if ($nominate.length) {
        $nominate.find('.info a').on('click', formShow);
        $nominate.find('button').on('click', nomineeSubmit);
        $nominate.find('input').on('keypress', nomineeKeypress);
        characterTypeahead = new Typeahead($txtName, characterChosen);
    }

}());