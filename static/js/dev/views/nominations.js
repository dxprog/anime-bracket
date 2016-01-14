import $ from 'jquery';

import Route from '../lib/route';

const IS_IE = (/MSIE/).test(window.navigator.userAgent);

export default Route('nominations', {
  initRoute() {

    this._$nominate = $('#page-nominate');
    this._$txtName = $('#txtName');
    this._$txtSource = $('#txtSource');
    this._$txtPic = $('#txtPic');
    this._$verified = $('[name="verified"]');
    this._$form = this._$nominate.find('form');
    this._$message = this._$form.find('.message');
    this._bracketId = this._$form.find('[name="bracketId"]').val();
    this._verified = false;

    this._$nominate
      .on('click', '.accept', this.formShow.bind(this))
      .on('click', 'button[type="submit"]', this.nomineeSubmit.bind(this))
      .on('keypress', 'input', this.nomineeKeypress.bind(this));
    // this._characterTypeahead = characterTypeahead = new Typeahead($txtName, characterChosen, bracketId);
  },

  displayMessage(message, success) {
    this._$message.removeClass('error success hidden').html(message).addClass(success ? 'success' : 'error');
  },

  nomineeCallback(data) {
    this.displayMessage(data.success ? '"' + this._$txtName.val() + '" was successfully nominated!' : data.message, data.success);
    this._$txtName.focus().val(data.success ? '' : this._$txtName.val());
    this._$txtSource.val(data.success ? '' : this._$txtSource.val());
    this._$txtPic.val(data.success ? '' : this._$txtPic.val());
    this._$txtPic.val(data.success ? '' : this._$txtPic.val());
    this._verified = data.success ? false : this._verified;
    this.setFormState(true);
  },

  nomineeKeypress(e) {
    if ((e.keyCode == 13 || e.charCode == 13) && !IS_IE) {
      this._nomineeSubmit(null);
    } else {
      this._verified = false;
    }
  },

  verifyImage() {
    var image = new Image,
      $dfr = $.Deferred();
    image.onload = $dfr.resolve;
    image.onerror = $dfr.reject;
    image.src = this._$txtPic.val();
    return $dfr.promise();
  },

  setFormState(enabled) {
    this._$txtName.prop('readonly', !enabled);
    this._$txtSource.prop('readonly', !enabled);
    this._$txtPic.prop('readonly', !enabled);
    this._$form.find('button').prop('disabled', !enabled);
  },

  nomineeSubmit(e) {

    var submit = this._$txtName.val().length && (!this._$txtSource.length || this._$txtSource.val().length) && this._$txtPic.val().length;

    if (null != e) {
      e.preventDefault();
    }

    this._$nominate.find('.error').removeClass('error');

    if (!submit) {
      if (!this._$txtName.val().length) {
        this._$txtName.addClass('error');
      }
      if (this._$txtSource.length && !this._$txtSource.val().length) {
        this._$txtSource.addClass('error');
      }
      if (!this._$txtPic.val().length) {
        this._$txtPic.addClass('error');
      }
    } else {
      this.setFormState(false);
      // Verified characters (one that has been nominated or added to the bracket already) get NOOPs
      this.verifyImage().done(() => {
        this._$verified.val(this._verified ? 'true' : 'false');
        $.ajax({
          url:'/submit/?action=nominate',
          dataType:'json',
          type:'POST',
          data: this._$form.serialize(),
          success: this.nomineeCallback.bind(this)
        });
      }).fail(() => {
        this.displayMessage('Invalid picture', false);
        this._$txtPic.addClass('error');
        this.setFormState(true);
      });

    }
  },

  formShow(e) {
    this._$nominate.find('.info').hide();
    this._$nominate.find('.form').show();
    this._$txtName.focus();
    e.preventDefault();
  },

  characterChosen(data) {
    if (null !== data) {
      this._$txtName.val(data.name);
      this._$txtSource.val(data.source);
      this._$txtPic.val(data.image).focus();
      this._verified = data.verified;
    }
  }

});