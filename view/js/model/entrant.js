(function(undefined) {
    
    var Entrant = window.Entrant = function(data, position) {
        this.position = position;
        if (null != data) {
            this.image = data.image;
            this.name = data.name;
            this.source = data.source;
            this.votes = parseInt(data.votes, 10);
            this.id = data.id;
        } else {
            this.image = 'unknown.jpg';
            this.name = '';
            this.source = '';
            this.id = 1;
            this.nobody = true;
        }
    };

}());