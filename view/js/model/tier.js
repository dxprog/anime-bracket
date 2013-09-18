(function(undefined) {
    
    var ENTRANT_HEIGHT = 100,

        Tier = window.Tier = function(data) {

        var rounds = [],
            i = 0,
            count = data.length,
            round = null,
            entrants = 0;

        for (; i < count; i++) {
            round = new Round(data[i]);
            entrants += round.entrants;
            rounds.push(round);
        }

        this._rounds = rounds;
        this.entrants = entrants;

    };

    Tier.prototype.render = function(tierOffset, group, split) {
        var rounds = [],
            i = 0,
            count = this._rounds.length,
            halfCount = 0,
            side = [],
            retVal = [],
            cellHeight = Math.pow(2, tierOffset + 1) * ENTRANT_HEIGHT / 2;

            if (undefined !== group && null !== group) {
                for (; i < count; i++) {
                    if (this._rounds[i].group === group) {
                        rounds.push(this._rounds[i]);
                    }
                }
            } else {
                rounds = this._rounds;
            }

        // Render such that when we're on the final round, each contestant is on opposite sides (for split render)
        count = rounds.length;
        if (count > 1 || !split) {
            for (i = 0, count = rounds.length, halfCount = count / 2; i < count; i++) {
                if (split && i > 0 && i % halfCount === 0) {
                    retVal.push(Templates['tier']({ side: 'left', height: cellHeight, rounds: side }));
                    side = [];
                }
                side.push(rounds[i]);
            }
            retVal.push(Templates['tier']({ side:(split ? 'right' : 'left'), height: cellHeight, rounds: side }));
        } else {
            retVal.push(Templates['tier']({
                side: 'left',
                height: cellHeight,
                rounds: [
                    { entrant1: rounds[0].entrant1 }
                ]
            }));
            retVal.push(Templates['tier']({
                side: 'right',
                height: cellHeight,
                rounds: [
                    { entrant1: rounds[0].entrant2 }
                ]
            }));
        }

        return retVal;
    };

    Tier.prototype.getRound = function(index, group) {
        var retVal = null,
            rounds = [],
            i = 0,
            count = this._rounds.length;

        if (group) {
            for (; i < count; i++) {
                if (this._rounds[i].group === group) {
                    rounds.push(this._rounds[i]);
                }
            }
        } else {
            rounds = this._rounds;
        }

        if (rounds.length > index) {
            retVal = rounds[index];
        }
        return retVal;
    }

}());