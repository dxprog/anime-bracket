import $ from 'jquery';

import Route from 'lib/route';

export default Route('admin-stats', {
  initRoute() {
    if (window.statsData) {
      $.getScript('/static/js/Chart.min.js').done((script) => {
        const data = this.getChartData(window.statsData);
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

        const canvas = document.getElementById('chart');
        canvas.setAttribute('width', $('#content').width());
        const ctx = canvas.getContext('2d');
        console.log(Chart);
        const chart = new Chart(ctx).Line(data);
      });
    }
  },

  getChartData(data) {
    const labels = [];
    const totalVotes = [];
    const userCount = [];

    data.forEach((item) => {
      labels.push('Round ' + (item.tier + 1) + ', Group ' + String.fromCharCode(item.group + 65));
      totalVotes.push(item.total);
      userCount.push(item.userTotal);
    });

    return {
      labels,
      totalVotes,
      userCount
    };
  }

});