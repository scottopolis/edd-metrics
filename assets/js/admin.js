(function(window, document, $, undefined){

  var eddm = {};

  eddm.init = function() {
    eddm.compare_temp_2 = window.eddMetrics.compare_string_2;
    eddm.revenue = window.eddMetrics.revenue;
    eddm.downloads = window.eddMetrics.downloads;
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

    var start = moment(this.start_date).format('LL'),
        end = moment(this.end_date).format('LL');

    //console.debug('Start Date: '+ start +'\nEnd Date: '+ end);

    var data = {
      'action': 'metrics_batch_1',
      'start': start,
      'end' : end
    };

    var loading = '<div id="circleG"><div id="circleG_1" class="circleG"></div><div id="circleG_2" class="circleG"></div><div id="circleG_3" class="circleG"></div></div>';

    $('.edd-metrics-box h2').html( loading );

    $('.edd-metrics-box .bottom-text span').html('').removeClass();

    if( $(this.element[0]).hasClass('metrics-detail') ) {

      $('.edd-metrics-chart-wrapper').append( loading );

      $.post( window.ajaxurl, data, eddm.detailResponse ).then( function() {

        data.action = 'metrics_batch_2';
        $.post( window.ajaxurl, data, eddm.detailResponse_2 );

      })
      .fail(function() {

        console.warn( "ajax error" );

      });

    } else {
      $.post( window.ajaxurl, data, eddm.dashResponse ).then( function() {

        data.action = 'metrics_batch_2';
        $.post( window.ajaxurl, data, eddm.batch2response );

      })
      .fail(function() {

        console.warn( "ajax error" );

      });
    }

  }

  eddm.dashResponse = function(response) {

    //console.log( 'dashresponse', response );

    var data = JSON.parse(response);

    var compareTemp = window.eddMetrics.compare_string + ' ' + data.dates.num_days + ' ' + window.eddMetrics.days;

    $('#revenue').html( data.earnings.total );
    $('#revenue-compare span').html( data.earnings.compare.percentage + compareTemp ).removeClass().addClass( data.earnings.compare.classes );

    $('#sales').html( data.sales.count );
    $('#sales-compare span').html( data.sales.compare.percentage + compareTemp ).removeClass().addClass( data.sales.compare.classes );

    // $('#yearly').html( data.earnings.avgyearly.total );
    // $('#avgyearly-compare span').html( data.earnings.avgyearly.compare.percentage + compareTemp ).removeClass().addClass( data.earnings.avgyearly.compare.classes );

    $('#avgpercust').html( data.earnings.avgpercust.total );
    $('#avgpercust-compare span').html( data.earnings.avgpercust.compare.percentage + compareTemp ).removeClass().addClass( data.earnings.avgpercust.compare.classes );

    $('#refunds').html( data.earnings.refunds.count );
    $('#refund-amount').html( data.earnings.refunds.losses );
    $('#refunds-compare span').html( data.earnings.refunds.compare.percentage + compareTemp ).removeClass().addClass( data.earnings.refunds.compare.classes );

  }

  eddm.batch2response = function(response) {

    // console.log( 'batch2response', response );

    var data = JSON.parse(response);

    var compareTemp = window.eddMetrics.compare_string + ' ' + data.dates.num_days + ' ' + window.eddMetrics.days;

    $('#earnings-today').html( data.earnings.detail.today );

    if( $('#renewals').length ) {
      $('#renewals').html( data.renewals.count );
      $('#renewal-amount').html( data.renewals.earnings );
      $('#renewals-compare span').html( data.renewals.compare.percentage + compareTemp ).removeClass().addClass( data.renewals.compare.classes );
    }

    if( $('#subscriptions').length ) {
      $('#subscriptions').html( data.subscriptions.number.count );
      $('#subscriptions-compare span').html( data.subscriptions.number.compare.percentage + compareTemp ).removeClass().addClass( data.subscriptions.number.compare.classes );

      $('#recurring-revenue').html( data.subscriptions.earnings.total );
      //$('#recurring-compare span').html( data.subscriptions.earnings.compare.percentage + compareTemp ).removeClass().addClass( data.subscriptions.earnings.compare.classes );

      $('#recurring-revenue-30').html( data.subscriptions.earnings30 );

    }

    $('#discounts').html( data.discounts.now.amount );
    $('#discounts-count').html( data.discounts.now.count );
    $('#discounts-compare span').html( data.discounts.compare.percentage + compareTemp ).removeClass().addClass( data.discounts.compare.classes );

    if( $('#commissions').length ) {
      $('#commissions').html( data.commissions.count );
      $('#commissions-amount').html( data.commissions.earnings );
    }

  }

  eddm.detailResponse = function(response) {

    //console.log( 'detailResponse', response );

    var data = JSON.parse(response);

    var metric = eddm.getQueryVariable('metric');

    switch( metric ) {
      case 'revenue':
          // do revenue

          $('#revenue').html( data.earnings.total );
          $('#revenue-compare span').html( data.earnings.compare.percentage + eddm.compare_temp_2 ).removeClass().addClass( data.earnings.compare.classes );
          $('.detail-compare-first').html( data.earnings.compare.total );

          $('#monthly h2').html( data.earnings.avgmonthly.earnings );

          $('#new-customers h2').html( data.earnings.avgpercust.current_customers );
          $('#new-customers span').html( 'This period' );

          // // Charts
          $('.detail-compare-first').html( data.earnings.compare.total );
          $('#box-4 .bottom-text span').html( data.earnings.compare.percentage + '%' );

          break;
      case 'renewals':
          // ...
          break;
      default:
          // ...
    }

    eddm.doLineChart( data.lineChart );

    $('.edd-metrics-chart-wrapper #circleG').remove();

  }

  eddm.detailResponse_2 = function(response) {

    var data = JSON.parse(response);

    // console.log( 'detailResponse_2', data );

    var metric = eddm.getQueryVariable('metric');

    switch( metric ) {
      case 'revenue':
          // do revenue

          $('#revenue-6mocompare span').html( data.earnings.detail.sixmoago.compare + eddm.compare_temp_2 ).removeClass().addClass( data.earnings.detail.sixmoago.classes );
          $('.detail-compare-second').html( data.earnings.detail.sixmoago.total );

          $('.detail-compare-third').html( data.earnings.detail.twelvemoago.total );
          $('#revenue-12mocompare span').html( data.earnings.detail.twelvemoago.compare + eddm.compare_temp_2 ).removeClass().addClass( data.earnings.detail.twelvemoago.classes );

          $('#earnings-today h2').html( data.earnings.detail.today );
          $('#earnings-this-month h2').html( data.earnings.detail.this_month );

          $('#renewal-rate h2').html( data.yearly_renewal_rate.percent + '%' );
          $('#yearly-renewal-compare span').html( 'Last ' + data.yearly_renewal_rate.period + ' days' );

          $('#box-5 .bottom-text span').html( data.earnings.detail.sixmoago.compare + '%' );
          $('.detail-compare-second').html( data.earnings.detail.sixmoago.total );

          $('.detail-compare-third').html( data.earnings.detail.twelvemoago.total );

          break;
      case 'renewals':
          // ...
          break;
      default:
          // ...
    }

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
                label: eddm.revenue,
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

    //console.log( chart );

    var data = {
        labels: chart.labels,
        datasets: [
            {
                label: eddm.downloads,
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

    var data = {
        labels: chart.labels,
        datasets: [
            {
                label: eddm.downloads,
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
