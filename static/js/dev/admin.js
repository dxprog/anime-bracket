(function() {
    

    var cropperInitialized = false,
        $cropper = null,
        imageSize = {},

        CROPPER_PADDING = 3,

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

            var cropperOptions = {
                    aspectRatio: 1,
                    minSize: [ 150, 150 ]
                },

                center = 0;

            if (!cropperInitialized) {
                if (imageSize) {
                    if (imageSize.width < imageSize.height) {
                        center = (imageSize.height - imageSize.width - CROPPER_PADDING * 2) / 2;
                        center = center < 0 ? 0 : center;
                        cropperOptions.setSelect = [ CROPPER_PADDING, center, imageSize.width - CROPPER_PADDING * 2, center + imageSize.width ];
                    } else {

                    }
                }
                $cropper.find('img').Jcrop(cropperOptions);
            }

            $cropper.fadeIn();

        },

        getImageDimensions = function(url) {
            var image = new Image;
            image.onload = function() {
                imageSize.width = image.width;
                imageSize.height = image.height;
            };
            image.src = url;
        };

    if ($('body#admin').length) {
        loadScripts([ '/static/js/jquery.Jcrop.min.js' ]).done(function() {
            
            $cropper = $('.overlay');
            getImageDimensions($cropper.find('img').attr('src'));
            $('body').on('click', '#changeImage', changeImageClick);

        });
    }

}());