(function(undefined) {
    
    var Entrant = window.Entrant = function(data, position) {
        this.image = data.image;
        this.name = data.name;
        this.source = data.source;
        this.votes = parseInt(data.votes, 10);
        this.position = position;
        this.id = data.id;
    };

}());