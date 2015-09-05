(function(undefined) {

    var characters = window._characters || null,
        seededSort = characters && new Array(characters.length),
        sourceSort = {},
        maxSeed = 0,
        nonSeedItr = characters && characters.length;

    function initSortData() {

        characters.forEach(function(character) {

            if (!sourceSort.hasOwnProperty(character.source)) {
                sourceSort[character.source] = [];
            }
            sourceSort[character.source].push(character)

            if (character.seed) {
                seededSort[character.seed - 1] = character;
                maxSeed = character.seed > maxSeed ? character.seed : maxSeed;
            } else {
                // Push non-seeded characters onto the back of the stack
                seededSort[--nonSeedItr] = character;
            }

        });

    }

    function resortEntrants(evt) {
        var sortBy = $(evt.currentTarget).val(),
            dataSets = [],
            template = Templates['characterList'];

        switch (sortBy) {
            case 'seed':
                dataSets.push({
                    header: 'Seeded Entrants',
                    characters: seededSort.slice(0, maxSeed - 1)
                });
                dataSets.push({
                    header: 'Eliminated Entrants',
                    characters: seededSort.slice(maxSeed)
                });
                break;
            case 'source':
                for (var source in sourceSort) {
                    if (!!sourceSort[source]) {
                        dataSets.push({
                            header: source,
                            characters: sourceSort[source]
                        });
                    }
                }
                break;
            default:
                dataSets.push({
                    characters: characters
                });
                break;
        }

        var out = '';
        dataSets.forEach(function(item) {
            out += template(item);
        });

        $('#roster').html(out);

    }

    if (characters) {
        initSortData();
        $('[name="sort"]').on('change', resortEntrants);
    }

}());