(function(undefined) {

    var $groups = $('#groups'),
        $entrants = $('#entrants'),
        $totalSize = $('.total-size'),
        totalEntrants = $entrants.data('total'),

        addEntrantOption = function(val, selected) {
            var opt = document.createElement('option');
            opt.value = val;
            opt.innerHTML = val;
            opt.selected = selected;
            $entrants.append(opt);
        },

        handleGroupsChange = function(evt) {
            var numGroups = $groups.val(),
                i = 2,
                selectedVal = $entrants.val();
                entrantsPerGroup = Math.floor(totalEntrants / numGroups);

            $entrants.empty();
            while (i <= entrantsPerGroup) {
                addEntrantOption(i, i == selectedVal);
                i *= 2;
            }

            updateBracketSize();
        },

        updateBracketSize = function() {
            var numGroups = $groups.val(),
                numEntrants = $entrants.val();
            $totalSize.html('Bracket will have ' + (numGroups * numEntrants) + ' total entrants.');
        },

        init = (function() {
            $groups.on('change', handleGroupsChange);
            $entrants.on('change', updateBracketSize);

            // Remove any groups that exceed the number of entrants
            $groups.find('option').each(function() {
                var $this = $(this),
                    val = $this.val();
                // Validate against the group count times two since each group has to have at least two entrants
                if (val * 2 > totalEntrants) {
                    $this.remove();
                }
            });

            handleGroupsChange();
        }());

}());