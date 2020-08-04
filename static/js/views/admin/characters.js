import $ from 'jquery';
import { Route } from 'molecule-router';
import React from 'react';
import ReactDOM from 'react-dom';

import AdminEntrantList from '../../components/AdminEntrantList';

const LOADING = 'loading';

export default Route('admin-characters', {
  _updateCharacter(evt) {
    const $target = $(evt.currentTarget);
    const $parent = $target.closest('tr');
    const $table = $parent.closest('table');
    const type = $table.data('type');
    let payload = {};

    $parent.addClass(LOADING);

    if ('nominee' === type) {
      payload = {
        id: $parent.data('id'),
        ignore: 'true'
      };
    } else if ('character' === type) {
      payload = {
        name: $parent.find('[name="name"]').val(),
        source: $parent.find('[name="source"]').val(),
        characterId: $parent.data('id'),
        action: $target.val()
      };
    }

    if (payload) {
      $.ajax({
        url: '/me/process/' + $table.data('bracket') + '/' + type + '/',
        type: 'POST',
        dataType: 'json',
        data: payload
      }).done((data) => {
        if (data.success) {
          $parent.removeClass(LOADING).addClass('success');
          if (data.action === 'delete' || 'nominee' === type) {
            $parent.remove();
          }
        } else {
          $parent.removeClass(LOADING).addClass('failed');
        }

      });
    }
  },

  deleteConfirmation(evt) {
    const retVal = window.confirm('All nominee, characters, and votes will be deleted. Do you wish to continue?');
    if (retVal) {
      this._updateCharacter(evt);
    }
  },

  initRoute() {
    if (window.initData) {
      ReactDOM.render(
        (
          <AdminEntrantList
            entrants={window.initData.characters}
            bracket={window.initData.bracket}
          />
        ),
        document.getElementById('characterList')
      );
    } else {
      this._$admin = $('#admin');
      $('.characters')
        .on('click', 'button.update', this._updateCharacter.bind(this))
        .on('click', 'button.delete', this.deleteConfirmation.bind(this));
    }
  }
});
