(function(undefined) {

    var canvas = null,
        ctx = null,
        chart = null,

        getChartData = function(data) {
            var labels = [],
                totalVotes = [],
                userCount = [];

            data.forEach(function(item) {
                labels.push('Round ' + (item.tier + 1) + ', Group ' + String.fromCharCode(item.group + 65));
                totalVotes.push(item.total);
                userCount.push(item.userTotal);
            });

            return {
                labels: labels,
                totalVotes: totalVotes,
                userCount: userCount
            };
        },

        init = function() {

            var data = {};

            if (window.statsData) {
                data = getChartData(window.statsData);
                console.log(data);
                data.datasets = [
                    {
                        label: 'Users',
                        fillColor: 'rgba(220,220,220,0.2)',
                        strokeColor: 'rgba(220,220,220,1)',
                        pointColor: 'rgba(220,220,220,1)',
                        pointStrokeColor: '#fff',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(220,220,220,1)',
                        data: data.userCount
                    },
                    {
                        label: 'Votes',
                        fillColor: 'rgba(151,187,205,0.2)',
                        strokeColor: 'rgba(151,187,205,1)',
                        pointColor: 'rgba(151,187,205,1)',
                        pointStrokeColor: '#fff',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(151,187,205,1)',
                        data: data.totalVotes
                    }
                ];
console.log(data);
                canvas = document.getElementById('chart');
                canvas.setAttribute('width', $('#content').width());
                ctx = canvas.getContext('2d');
                chart = new Chart(ctx).Line(data);
            }

        };

    $(init);

}());