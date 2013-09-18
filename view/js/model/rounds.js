(function(undefined) {

    var Round = window.Round = function(data) {
        this.group = parseInt(data.group, 10);
        this.order = parseInt(data.order, 10);
        this.entrants = 1;
        this.entrant1 = new Entrant(data.character1, 'top');
        this.id = data.id;
        
        // Character ID is reserved for "nobody", used in wildcard and eliminations
        if (this.entrant1.id != 1) {
            this.entrant2 = new Entrant(data.character2, 'bottom');
            this.entrants = 2;
        }
    };

}());