import $ from 'jquery';
import { Route } from 'molecule-router';

const bracketId = window.bracketId;

export default Route('voting', {

  initRoute() {

    this._$message = $('.message');
    this._$votesCode = $('.votes-code');
    this._$overlay = this._$votesCode.find('.overlay');
    this._$markdown = $('#votes_markdown');

    // For deselected an already selected radio
    $('#vote-form')
      .on('click', '[type="radio"]:not([disabled]) + label', this.deselectEntrant.bind(this))
      .on('submit', this.formSubmit.bind(this));

    this._$votesCode.on('click', 'button', this.showMarkdownModal.bind(this));
    this._$overlay.on('click', this.hideMarkdownModal.bind(this));

    // If the user has already voted, show the markdown button
    if ($('input:checked').length) {
      this._$votesCode.show();
    }

  },

  deselectEntrant(evt) {
    var radio = document.getElementById(evt.currentTarget.getAttribute('for'));
    if (radio.checked) {
      radio.checked = false;
      evt.preventDefault();
    }
  },

  setMessage(message, success) {
    $(window).scrollTop(0);
    this._$message.html(message).removeClass('hidden error success').addClass(success ? 'success' : 'error')
  },

  showMarkdownModal(evt) {
    var markdown = '';

    $('.voting li').each(function() {
      let $this = $(this);
      let voted = !!$this.find('input:checked').length;
      let name = $this.find('.mini-card__name').text();
      let img = $this.find('img').attr('src');
      let isFirst = $this.hasClass('entrant1');

      // This might actually be the most disgusting single line of code I've ever written... I'm so proud of myself!
      // 2020 update: made slightly less ugly with JS string templates!
      markdown += ` - [' + ${voted ? '**' : '~~'}${name}${voted ? '**' : '~~'}](${img})${isFirst ? '' : '\n'}`;
    });

    this._$markdown.val(markdown);
    this._$overlay.fadeIn(() => this._$markdown.select());
  },

  hideMarkdownModal(evt) {
    if (evt.target.tagName !== 'TEXTAREA') {
      this._$overlay.fadeOut();
    }
  },

  formSubmit(evt) {
    var $this = $(evt.currentTarget);
    const $button = $this.find('button[type="submit"]');
    evt.preventDefault();
    $button.prop('disabled', true);

    $.ajax({
      url: $this.attr('action'),
      data: $this.serialize(),
      dataType: 'json',
      type: 'POST'
    }).done((data) => {
      $button.prop('disabled', false);
      this.setMessage(data.message, data.success);

      if (data.success) {
        // Disable all the rounds the user voted on
        $('input:checked').each(function() {
          var name = this.getAttribute('name');
          $('[name="' + name + '"]').prop('disabled', true);
        });

        this._$votesCode.show();
      }
    }).fail(() => {
      $button.prop('disabled', false);
      this.setMessage('There was an unexpected error talking to the server. Please try again in a few moments.', false);
    });

  }

});
