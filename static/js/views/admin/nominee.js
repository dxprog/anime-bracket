import $ from 'jquery';
import { Route } from 'molecule-router';

import Nominee from '@views/admin/nominee.hbs';
import '../../../scss/jquery.Jcrop.min.scss';

const CHECKED = 'checked';
const UPLOAD_ENDPOINT = '/me/image/upload/';
const CROP_ENDPOINT = '/me/image/crop/';
const SUBMIT_ENDPOINT = '/me/process/nominee/';
const CROPPER_PADDING = 3;
const ACCEPTED_FORMATS = [
  'image/jpeg',
  'image/gif',
  'image/png'
];

export default Route('admin-nominee', {
  initRoute() {
    // This is some temporary hackery to get the cropper to work until I write my own
    window.jQuery = $;
    let script = document.createElement('script');
    document.getElementsByTagName('head')[0].appendChild(script);
    script.src = '/static/js/jquery.Jcrop.min.js';

    $('body')
      .on('click', '#changeImage', this.changeImageClick.bind(this))
      .on('change', '[name="upload"]', this.uploadChange.bind(this))
      .on('click', '.crop-submit', this.submitCrop.bind(this))
      .on('change', 'input[type="checkbox"]', this.ignoreCheck.bind(this))
      .on('submit', 'form', this.submitNominee.bind(this))
      .on('click', 'button.delete', this.deleteNominee.bind(this))
      .on('click', 'button.copy', this.copyCharacterClick.bind(this))
      .on('click', 'button.cancel', this.hideCropper.bind(this))
      .on('click', '.overlay', this.hideCropper.bind(this));
    this.renderComplete();
  },

  changeImageClick(evt) {
    this.initCropper();
    this._$cropper.fadeIn();
  },

  displayMessage(message, success) {
    $('.message').html(message).removeClass('hidden error success').addClass(success ? 'success' : 'error');
    $(window).scrollTop(0);
  },

  initCropper(reInit) {
    if (!this._cropperInitialized || reInit) {
      let cropperOptions = {
        aspectRatio: 1,
        minSize: [ 150, 150 ],
        onSelect: this.selectionUpdated.bind(this)
      };

      let center = 0;

      this.getImageDimensions().done((imageSize) => {
        if (imageSize.width < imageSize.height) {
          center = (imageSize.height - imageSize.width - CROPPER_PADDING * 2) / 2;
          center = center < 0 ? 0 : center;
          cropperOptions.setSelect = [ CROPPER_PADDING, center, imageSize.width - CROPPER_PADDING * 2, center + imageSize.width ];
        } else {
          center = (imageSize.width - imageSize.height - CROPPER_PADDING * 2) / 2;
          center = center < 0 ? 0 : center;
          cropperOptions.setSelect = [ center, CROPPER_PADDING, center + imageSize.height, imageSize.height - CROPPER_PADDING * 2 ];
        }
        this._$cropper.find('img').Jcrop(cropperOptions);
        this._cropperInitialized = true;
      });
    }
  },

  uploadComplete(data) {
    if (data.success) {
      this._$cropper.find('.cropper').empty().append('<img src="' + data.fileName + '" />');
      this.initCropper(true);
    } else {
      alert(data.message);
    }
  },

  selectionUpdated(coords) {
    this._cropCoords = coords;
  },

  submitNominee(evt) {
    evt.preventDefault();

    let $form = $(evt.currentTarget);
    let data = $form.serialize();

    $.ajax({
      url: $form.attr('action'),
      data: data,
      dataType: 'json',
      type: 'POST'
    }).done((data) => {

      if (data.success) {
        $('#content').html(Nominee(data));
        this.renderComplete();
        this.displayMessage(data.message, true);
      } else {
        this.displayMessage(data.message, false);
      }

    }).fail(() => {
      this.displayMessage('There was an error sending to the server', false);
    });

  },

  deleteNominee(evt) {
    $(evt.currentTarget).closest('form').append('<input type="hidden" name="ignore" value="true" />');
  },

  ignoreCheck(evt) {
    var $parent = $(evt.target).closest('tr');
    if (evt.target.checked) {
      $parent.addClass(CHECKED);
      $parent.find('label.button').text('Not Duplicate');
    } else {
      $parent.removeClass(CHECKED);
      $parent.find('label.button').text('Is Duplicate');
    }
  },

  submitCrop(evt) {

    evt.preventDefault();

    var imageFile = this._$cropper.find('img').attr('src');
    $.ajax({
      url: CROP_ENDPOINT,
      type: 'POST',
      dataType: 'json',
      data: {
        imageFile: imageFile,
        x: this._cropCoords.x,
        y: this._cropCoords.y,
        width: this._cropCoords.w,
        height: this._cropCoords.h
      }
    }).done((data) => {
      if (data.success) {
        // Replace the old image
        $('.nominee .image img').attr('src', data.fileName);
        $('[name="imageFile"]').val(data.fileName);
        this.hideCropper();
      } else {
        alert(data.message);
      }
    }).fail(() => {
      alert('Unable to crop image');
    });


  },

  hideCropper(evt) {
    // We'll only close if this method was called directly,
    // on the click of the cancel button, or clicking the overlay
    if (!evt || evt.target.className === 'overlay' || evt.target.className === 'cancel') {
      this._$cropper.fadeOut();
    }
  },

  uploadChange(evt) {

    let xhr = new XMLHttpRequest();
    let files = evt.target.files;
    let file = '';
    let formData = new FormData();

    if (files.length > 0) {
      file = files[0];

      // Validate the type
      if (ACCEPTED_FORMATS.indexOf(file.type) === -1) {
        alert('Image must be a JPEG, GIF, or PNG');
        return;
      }

      xhr.addEventListener('readystatechange', () => {
        if (xhr.readyState === 4) {
          try {
            this.uploadComplete(JSON.parse(xhr.responseText));
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

  copyCharacterClick(evt) {
    var $target = $(evt.currentTarget);
    $('[name="name"]').val($target.data('name'));
    $('[name="source"]').val($target.data('source'));
    $('[name="imageFile"]').val($target.data('image'));
    $('.image img').attr('src', $target.data('image'));
  },

  getImageDimensions() {
    var image = new Image,
      $dfr = $.Deferred(),
      url = this._$cropper.find('img').attr('src');
    image.onload = () => {
      $dfr.resolve({
        width: image.width,
        height: image.height
      });
    };
    image.src = url;
    return $dfr.promise();
  },

  renderComplete() {
    this._$cropper = $('.overlay');
    this._cropperInitialized = false;
  }

});
