(function(window, document, $, undefined){

  var eddm = {};

  eddm.init = function() {
    eddm.currencySign = window.edd_vars.currency_sign;
    eddm.doCal();
  }

  eddm.doCal = function() {

    var dd = new Calendar({
      element: $('.metrics-datepicker'),
      presets: [{
        label: 'Last 30 days',
        start: moment().subtract(29, 'days'),
        end: moment()
      },{
        label: 'This month',
        start: moment().startOf('month'),
        end: moment().endOf('month')
      },{
        label: 'Last month',
        start: moment().subtract(1, 'month').startOf('month'),
        end: moment().subtract(1, 'month').endOf('month')
      },{
        label: 'Today',
        start: moment().startOf('day'),
        end: moment()
      },{
        label: 'Last 7 days',
        start: moment().subtract(6, 'days'),
        end: moment()
      },{
        label: 'Last 3 months',
        start: moment(this.latest_date).subtract(3, 'month').startOf('month'),
        end: moment(this.latest_date).subtract(1, 'month').endOf('month')
      }],
      earliest_date: 'January 1, 2006',
      latest_date: moment(),
      start_date: moment().subtract(29, 'days'),
      end_date: moment(),
      callback: eddm.callback
    });

    // run it with defaults
    dd.calendarSaveDates();

  }

  eddm.callback = function() {

    var start = moment(this.start_date).format('ll'),
        end = moment(this.end_date).format('ll');

    console.debug('Start Date: '+ start +'\nEnd Date: '+ end);

    var data = {
      'action': 'edd_metrics_change_date',
      'start': start,
      'end' : end
    };

    var compareTemp = '% over the last ';

    $('.edd-metrics-box h2').html('<div id="circleG"><div id="circleG_1" class="circleG"></div><div id="circleG_2" class="circleG"></div><div id="circleG_3" class="circleG"></div></div>');

    $('.edd-metrics-box .bottom-text span').html('').removeClass();

    if( $(this.element[0]).hasClass('metrics-detail') ) {
      $.post( window.ajaxurl, data, eddm.detailResponse );
    } else {
      $.post( window.ajaxurl, data, eddm.dashResponse );
    }

  }

  eddm.dashResponse = function(response) {

    var data = JSON.parse(response);

    console.log( data );

    var compareTemp = '% over previous ' + data.dates.num_days + ' days';

    $('#revenue').text( eddm.currencySign + data.earnings.total );
    $('#revenue-compare span').text( data.earnings.compare.percentage + compareTemp ).removeClass().addClass( data.earnings.compare.classes );

    $('#sales').text( data.sales.count );
    $('#sales-compare span').text( data.sales.compare.percentage + compareTemp ).removeClass().addClass( data.sales.compare.classes );

    $('#yearly').text( eddm.currencySign + data.earnings.avgyearly.total );
    $('#avgyearly-compare span').text( data.earnings.avgyearly.compare.percentage + compareTemp ).removeClass().addClass( data.earnings.avgyearly.compare.classes );

    $('#avgpercust').text( eddm.currencySign + data.earnings.avgpercust.total );
    $('#avgpercust-compare span').text( data.earnings.avgpercust.compare.percentage + compareTemp ).removeClass().addClass( data.earnings.avgpercust.compare.classes );

    $('#renewals').text( data.renewals.count );
    $('#renewal-amount').text( eddm.currencySign + data.renewals.earnings );
    $('#renewals-compare span').text( data.renewals.compare.percentage + compareTemp ).removeClass().addClass( data.renewals.compare.classes );

    $('#refunds').text( data.refunds.count );
    $('#refund-amount').text( eddm.currencySign + data.refunds.losses );
    $('#refunds-compare span').text( data.refunds.compare.percentage + compareTemp ).removeClass().addClass( data.refunds.compare.classes );

    $('#subscriptions').text( data.subscriptions.count );
    $('#subscriptions-compare span').text( data.subscriptions.compare.percentage + compareTemp ).removeClass().addClass( data.subscriptions.compare.classes );

    $('#discounts').text( eddm.currencySign + data.discounts.amount );

    // Charts
    $('.detail-compare-first').text( eddm.currencySign + data.earnings.compare.total );
    $('#box-4 .bottom-text span').text( data.earnings.compare.percentage + '%' );

    $('#box-5 .bottom-text span').text( data.earnings.detail.sixmoago.compare + '%' );
    $('.detail-compare-second').text( eddm.currencySign + data.earnings.detail.sixmoago.total );

    $('.detail-compare-third').text( eddm.currencySign + data.earnings.detail.twelvemoago.total );
    
  }

  eddm.detailResponse = function(response) {

    var data = JSON.parse(response);

    console.log( 'detailResponse', data );
    
    var metric = eddm.getQueryVariable('metric');

    var compareTemp = '% compared to this period';

    switch( metric ) {
      case 'revenue':
          // do revenue

          $('#revenue').text( eddm.currencySign + data.earnings.total );
          $('#revenue-compare span').text( data.earnings.compare.percentage + compareTemp ).removeClass().addClass( data.earnings.compare.classes );
          $('.detail-compare-first').text( eddm.currencySign + data.earnings.compare.total );

          $('#revenue-6mocompare span').text( data.earnings.detail.sixmoago.compare + compareTemp ).removeClass().addClass( data.earnings.detail.sixmoago.classes );
          $('.detail-compare-second').text( eddm.currencySign + data.earnings.detail.sixmoago.total );

          $('.detail-compare-third').text( eddm.currencySign + data.earnings.detail.twelvemoago.total );
          $('#revenue-12mocompare span').text( data.earnings.detail.twelvemoago.compare + compareTemp ).removeClass().addClass( data.earnings.detail.twelvemoago.classes );

          $('#earnings-today h2').text( eddm.currencySign + data.earnings.detail.today );
          $('#earnings-this-month h2').text( eddm.currencySign + data.earnings.detail.this_month );

          $('#monthly h2').text( eddm.currencySign + data.earnings.avgmonthly.earnings );

          $('#renewal-rate h2').text( data.yearly_renewal_rate.percent + '%' );
          $('#yearly-renewal-compare span').text( 'Last ' + data.yearly_renewal_rate.period + ' days' );

          break;
      case 'renewals':
          // ...
          break;
      default:
          // ...
    }

    eddm.doLineChart( data.lineChart );
    eddm.doDownloadChart( data.pieChart );
    eddm.doGatewayChart( data.earnings.gateways );
    
  }

  eddm.getQueryVariable = function (variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i=0;i<vars.length;i++) {
           var pair = vars[i].split("=");
           if(pair[0] == variable){return pair[1];}
    }
    return(false);
  }

  eddm.doLineChart = function( chart ) {

    if( eddm.lineChart ) {
      eddm.lineChart.destroy();
    }

    var data = {
        labels: chart.labels,
        datasets: [
            {
                label: "Revenue",
                fill: true,
                lineTension: 0.1,
                backgroundColor: "rgba(0,115,170,.2)",
                borderColor: "#0073aa",
                borderCapStyle: 'butt',
                borderDash: [],
                borderDashOffset: 0.0,
                borderJoinStyle: 'miter',
                pointBorderColor: "#0073aa",
                pointBackgroundColor: "#fff",
                pointBorderWidth: 2,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: "#0073aa",
                pointHoverBorderColor: "rgba(220,220,220,1)",
                pointHoverBorderWidth: 2,
                pointRadius: 4,
                pointHitRadius: 10,
                data: chart.earnings,
                spanGaps: false
            },

            // {
            //   label: "Sales",
            //   data: chart.sales
            // }
        ]
    };

    var ctx = document.getElementById("metrics-line-chart");

    eddm.lineChart = new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            scales: {
                xAxes: [{
                    display: false,
                    stacked: true
                }],
            }
        }
    });
  }

  eddm.doDownloadChart = function( chart ) {

    if( eddm.downloadChart ) {
      eddm.downloadChart.destroy();
    }

    // console.log( chart );

    var data = {
        labels: chart.labels,
        datasets: [
            {
                label: "Downloads",
                data: chart.earnings,
                backgroundColor: [
                  "#225378",
                  "#1695A3",
                  "#1fa739",
                  "#EB7F00",
                  "#EB4B4D",
                  "#EBA945",
                  "#663300",
                  "#79EB65"
                ],
                hoverBackgroundColor: [
                  "#225378",
                  "#1695A3",
                  "#1fa739",
                  "#EB7F00",
                  "#EB4B4D",
                  "#EBA945",
                  "#663300",
                  "#79EB65"
                ]
            }
        ]
    };

    var ctx = document.getElementById("metrics-piechart-by-download");

    eddm.downloadChart = new Chart(ctx,{
        type: 'pie',
        data: data
    });

  }

  eddm.doGatewayChart = function( chart ) {

    if( eddm.gatewayChart ) {
      eddm.gatewayChart.destroy();
    }

    console.log( chart );

    var data = {
        labels: chart.labels,
        datasets: [
            {
                label: "Downloads",
                data: chart.earnings,
                backgroundColor: [
                  "#225378",
                  "#1695A3",
                  "#1fa739",
                  "#EB7F00",
                  "#EB4B4D",
                  "#EBA945",
                  "#663300",
                  "#79EB65"
                ],
                hoverBackgroundColor: [
                  "#225378",
                  "#1695A3",
                  "#1fa739",
                  "#EB7F00",
                  "#EB4B4D",
                  "#EBA945",
                  "#663300",
                  "#79EB65"
                ]
            }
        ]
    };

    var ctx = document.getElementById("metrics-piechart-by-gateway");

    eddm.gatewayChart = new Chart(ctx,{
        type: 'pie',
        data: data
    });

  }

  jQuery(document).ready( eddm.init );

})(window, document, jQuery);