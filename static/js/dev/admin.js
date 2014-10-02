(function(undefined) {
    
    'use strict';

    var cropperInitialized = false,
        $cropper = null,
        imageSize = {},

        UPLOAD_ENDPOINT = '/admin/upload/',
        CROP_ENDPOINT = '/admin/crop/',
        CROPPER_PADDING = 3,
        ACCEPTED_FORMATS = [
            'image/jpeg',
            'image/gif',
            'image/png'
        ],

        cropCoords = {},

        /**
         * Multiple script loader
         * @param {Array} scripts Array of scripts to load
         * @return {Promise} jQuery promise that is resolved when all scripts have been loaded
         */
        loadScripts = function(scripts) {
            var $dfr = $.Deferred(),

                scriptLoaded = function() {
                    if (scripts.length > 0) {
                        $.getScript(scripts.shift()).done(scriptLoaded);
                    } else {
                        $dfr.resolve();
                    }
                };

            $.getScript(scripts.shift()).done(scriptLoaded);

            return $dfr.promise();
        },

        changeImageClick = function(evt) {

            if (!cropperInitialized) {
                initCropper();
            }

            $cropper.fadeIn();

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

        submitCrop = function(evt) {
            alert('blah');
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

        initNomineeForm = function() {
            loadScripts([ '/static/js/jquery.Jcrop.min.js' ]).done(function() {
                $cropper = $('.overlay');
                $('body')
                    .on('click', '#changeImage', changeImageClick)
                    .on('change', '[name="upload"]', uploadChange)
                    .on('click', '.crop-submit', submitCrop);
            });
        };

    if ($('body#admin .nominee').length) {
        initNomineeForm();
    }

}());