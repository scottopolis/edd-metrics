(function(window, document, $, undefined){

  var eddm = {};

  eddm.init = function() {
    eddm.doCal();
  }

  eddm.doCal = function() {
    var dd = new Calendar({
      element: $('.metrics-datepicker'),
      earliest_date: 'January 1, 2006',
      latest_date: moment(),
      start_date: moment().subtract(29, 'days'),
      end_date: moment(),
      callback: function() {
        var start = moment(this.start_date).format('ll'),
            end = moment(this.end_date).format('ll');

        console.debug('Start Date: '+ start +'\nEnd Date: '+ end);

        var data = {
          'action': 'edd_metrics_change_date',
          'start': start,
          'end' : end
        };

        $.post( window.ajaxurl, data, function(response) {
          console.log( response );
          var data = JSON.parse(response);
          console.log( data );

          $('#revenue').text( '$' + data.earnings.total );
          $('#revenue-compare').html( data.earnings.compare );

          $('#sales').text( data.sales.count );
          $('#sales-compare').html( data.sales.compare );

          $('#yearly').text( '$' + data.earnings.avgyearly.total );
          $('#avgyearly-compare').html( data.earnings.avgyearly.compare );

          $('#avgpercust').text( '$' + data.earnings.avgpercust );

          $('#renewals').text( data.renewals.count );

          $('#refunds').text( data.refunds.count );

        });
      }
    });

  }

  jQuery(document).ready( eddm.init );

})(window, document, jQuery);