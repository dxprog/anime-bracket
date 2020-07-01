import $ from 'jquery';
import { Route } from 'molecule-router';

export default Route('admin-start-bracket', {
  initRoute() {
    this._$groups = $('#groups').on('change', this.handleGroupsChange.bind(this));
    this._$entrants = $('#entrants').on('change', this.updateBracketSize.bind(this));
    this._$totalSize = $('.total-size');
    this._totalEntrants = this._$entrants.data('total');

    // Remove any groups that exceed the number of entrants
    this._$groups.find('option').each(function() {
      const $this = $(this);
      const val = $this.val();

      // Validate against the group count times two since each group has to have at least two entrants
      if (val * 2 > this._totalEntrants) {
        $this.remove();
      }
    });

    this.handleGroupsChange();
  },

  addEntrantOption(val, selected) {
    let opt = document.createElement('option');
    opt.value = val;
    opt.innerHTML = val;
    opt.selected = selected;
    this._$entrants.append(opt);
  },

  handleGroupsChange(evt) {
    let numGroups = this._$groups.val();
    let i = 2;
    let selectedVal = this._$entrants.val();
    let entrantsPerGroup = Math.floor(this._totalEntrants / numGroups);

    this._$entrants.empty();
    while (i <= entrantsPerGroup) {
      this.addEntrantOption(i, i == selectedVal);
      i *= 2;
    }

    this.updateBracketSize();
  },

  updateBracketSize() {
    const entrantCount = this._$groups.val() * this._$entrants.val();
    this._$totalSize.html(`Bracket will have ${entrantCount} total entrants.`);
  }

});