<?php

// if( empty( $_GET['metric'] ) ) {
//     echo '<p>Nothing to display here. Please go back to the main metrics page and select a metric.</p>';
// }

// $edd_metrics_detail_class = new EDD_Metrics_Detail();

// switch ( $_GET['metric'] ) {
//     case 'revenue':
//         $edd_metrics_detail_class->revenue_callback();
//         break;
//     case 'renewals':
//         $edd_metrics_detail_class->renewals_callback();
//         break;
//     case 'refunds':
//         $edd_metrics_detail_class->refunds_callback();
//         break;
//     default:
//         $edd_metrics_detail_class->revenue_callback();
//         break;
// }

?>

<div class="daterange daterange--double metrics-datepicker metrics-detail"></div>

<h1><?php _e('Revenue Detail', 'edd-metrics'); ?></h2>

<a href="<?php echo admin_url(); ?>edit.php?post_type=download&page=edd_metrics" class="metrics-details-link" style="float:right;margin-top:15px">&larr; <?php _e('Back to Overview', 'edd-metrics'); ?></a>

<?php do_action('edd_metrics_before_detail'); ?>

<h2 id="revenue" class="metrics-big-title"></h2>

<div class="edd-metrics-box edd-metrics-chart-wrapper">
    <canvas id="metrics-line-chart" width="400" height="150"></canvas>
</div>

<div class="edd-metrics-box" style="margin-bottom:15px">
    <div class="one-third" id="box-4">
        <p class="top-text"><?php _e('Previous period', 'edd-metrics'); ?></p>
        <h2 class="detail-compare-first"></h2>
        <p class="bottom-text" id="revenue-compare"><span></span></p>
    </div>

    <div class="one-third" id="box-5">
        <p class="top-text"><?php _e('6 months ago', 'edd-metrics'); ?></p>
        <h2 class="detail-compare-second"></h2>
        <p class="bottom-text" id="revenue-6mocompare"><span></span></p>
    </div>

    <div class="one-third last-col" id="box-6">
        <p class="top-text"><?php _e('12 months ago', 'edd-metrics'); ?></p>
        <h2 class="detail-compare-third"></h2>
        <p class="bottom-text" id="revenue-12mocompare"><span></span></p>
    </div>
</div>

<div class="edd-metrics-box edd-metrics-chart-wrapper one-half">
    <h3><?php _e('Earnings by download', 'edd-metrics'); ?></h3>
    <canvas id="metrics-pie-chart" width="200" height="200"></canvas>
</div>

<div class="one-half last-col">
    <?php do_action('edd_metrics_dash_sidebar'); ?>
</div>

<?php do_action('edd_metrics_after_detail'); ?>