(function() {

    var cropperInitialized = false,
        $cropper = null,
        imageSize = {},

        CHECKED = 'checked',
        UPLOAD_ENDPOINT = '/me/image/upload/',
        CROP_ENDPOINT = '/me/image/crop/',
        SUBMIT_ENDPOINT = '/me/process/nominee/'
        CROPPER_PADDING = 3,
        ACCEPTED_FORMATS = [
            'image/jpeg',
            'image/gif',
            'image/png'
        ],

        cropCoords = {},

        changeImageClick = function(evt) {

            if (!cropperInitialized) {
                initCropper();
            }

            $cropper.fadeIn();

        },

        displayMessage = function(message, success) {
            $('.message').html(message).removeClass('hidden error success').addClass(success ? 'success' : 'error');
            $(window).scrollTop(0);
        },

        initCropper = function() {

            var cropperOptions = {
                    aspectRatio: 1,
                    minSize: [ 150, 150 ],
                    onSelect: selectionUpdated
                },

                center = 0;

            getImageDimensions().done(function(imageSize) {
                if (imageSize.width < imageSize.height) {
                    center = (imageSize.height - imageSize.width - CROPPER_PADDING * 2) / 2;
                    center = center < 0 ? 0 : center;
                    cropperOptions.setSelect = [ CROPPER_PADDING, center, imageSize.width - CROPPER_PADDING * 2, center + imageSize.width ];
                } else {
                    center = (imageSize.width - imageSize.height - CROPPER_PADDING * 2) / 2;
                    center = center < 0 ? 0 : center;
                    cropperOptions.setSelect = [ center, CROPPER_PADDING, center + imageSize.height, imageSize.height - CROPPER_PADDING * 2 ];
                }
                $cropper.find('img').Jcrop(cropperOptions);
                cropperInitialized = true;
            });
        },

        uploadComplete = function(data) {

            if (data.success) {
                cropperInitialized = false;
                $cropper.find('.crop').empty().append('<img src="' + data.fileName + '" />');
                initCropper();
            } else {
                alert(data.message);
            }

        },

        selectionUpdated = function(coords) {
            cropCoords = coords;
        },

        submitNominee = function(evt) {
            evt.preventDefault();

            var $form = $('form'),
                data = $form.serialize(),
                ignore = $(evt.target).val() ? 'ignore=true' : '';

            if (ignore) {
                ignore = (data.length ? '&' : '') + ignore;
                data += ignore;
            }

            $.ajax({
                url: $form.attr('action'),
                data: data,
                dataType: 'json',
                type: 'POST'
            }).done(function(data) {

                if (data.success) {
                    $('#content').html(Templates['views/admin/nominee'](data));
                    renderComplete();
                    displayMessage(data.message, true);
                } else {
                    displayMessage(data.message, false);
                }

            }).fail(function() {
                displayMessage('There was an error sending to the server', false);
            });

        },

        ignoreCheck = function(evt) {
            var $parent = $(evt.target).closest('tr');
            if (evt.target.checked) {
                $parent.addClass(CHECKED);
                $parent.find('label.button').text('Not Duplicate');
            } else {
                $parent.removeClass(CHECKED);
                $parent.find('label.button').text('Is Duplicate');
            }
        },

        submitCrop = function(evt) {

            evt.preventDefault();

            var imageFile = $cropper.find('img').attr('src');
            $.ajax({
                url: CROP_ENDPOINT,
                type: 'POST',
                dataType: 'json',
                data: {
                    imageFile: imageFile,
                    x: cropCoords.x,
                    y: cropCoords.y,
                    width: cropCoords.w,
                    height: cropCoords.h
                }
            }).done(function(data) {
                if (data.success) {
                    // Replace the old image
                    $('.nominee .image img').attr('src', data.fileName);
                    $('[name="imageFile"]').val(data.fileName);
                    $cropper.fadeOut();
                } else {
                    alert(data.message);
                }
            }).fail(function() {
                alert('Unable to crop image');
            });


        },

        uploadChange = function(evt) {

            var xhr = new XMLHttpRequest(),
                files = evt.target.files,
                file = '',
                self = this,
                formData = new FormData();

            if (files.length > 0) {

                file = files[0];

                // Validate the type
                if (ACCEPTED_FORMATS.indexOf(file.type) === -1) {
                    alert('Image must be a JPEG, GIF, or PNG');
                    return;
                }

                xhr.addEventListener('readystatechange', function() {
                    if (xhr.readyState === 4) {
                        try {
                            uploadComplete(JSON.parse(xhr.responseText));
                        } catch (e) {
                            // do nothing because I hate error management. Someday...
                        }
                    }
                });

                xhr.open('POST', UPLOAD_ENDPOINT, true);
                xhr.setRequestHeader('X-FileName', file.name);
                formData.append('upload', file);
                xhr.send(formData);

            }

        },

        copyCharacterClick = function(evt) {
            var $target = $(evt.currentTarget);
            $('[name="name"]').val($target.data('name'));
            $('[name="source"]').val($target.data('source'));
            $('[name="imageFile"]').val($target.data('image'));
            $('.image img').attr('src', $target.data('image'));
        },

        getImageDimensions = function() {
            var image = new Image,
                $dfr = $.Deferred(),
                url = $cropper.find('img').attr('src');
            image.onload = function() {
                $dfr.resolve({
                    width: image.width,
                    height: image.height
                });
            };
            image.src = url;
            return $dfr.promise();
        },

        renderComplete = function() {
            $cropper = $('.overlay');
            cropperInitialized = false;
        },

        init = function() {
            $('body')
                .on('click', '#changeImage', changeImageClick)
                .on('change', '[name="upload"]', uploadChange)
                .on('click', '.crop-submit', submitCrop)
                .on('change', 'input[type="checkbox"]', ignoreCheck)
                .on('click', '.buttons button', submitNominee)
                .on('click', 'button.copy', copyCharacterClick)
                .on('click', 'button.same', submitNominee);
            renderComplete();
        };

    init();

}());