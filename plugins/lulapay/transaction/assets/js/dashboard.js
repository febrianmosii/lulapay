$(document).ready(function () {
    $.each($('.apex-chart'), function (indexInArray, valueOfElement) { 
        series = $(this).attr('data-series').split(',');
        labels = $(this).attr('data-labels').split(',');
        
        data = {
            'series': series,
            'labels': labels
        }

        createChart($(this).attr('id'), $(this).attr('data-chart-type'), data);
    });

});

function createChart(id, type, data) 
{
    var options = {
        series: data.series,
        labels: data.labels,
        chart: {
            type: type,
            width: '100%',
        },
        responsive: [{
            breakpoint: 480,
            options: {
              chart: {
                width: '100%'
              },
              legend: {
                position: 'bottom'
              }
            }
        }]
    };

    window['chart_' + id] = new ApexCharts(document.querySelector("#" + id), options);;
    window['chart_' + id].render();
}