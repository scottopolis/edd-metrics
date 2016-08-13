<?php

if( empty( $_GET['metric'] ) ) {
    echo '<p>Nothing to display here. Please go back to the main metrics page and select a metric.</p>';
}

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

<h2 class="page-title">Metrics Detail</h2>

<?php do_action('edd_metrics_before_detail'); ?>

<div class="one-third">
    <div class="edd-metrics-box" id="box-1">
        <p class="top-text">Total</p>
        <h2></h2>
    </div>
</div>

<div class="one-third">
    <div class="edd-metrics-box" id="box-2">
        <p class="top-text"></p>
        <h2></h2>
        <p class="bottom-text"><span></span></p>
    </div>
</div>

<div class="one-third last-col">
    <div class="edd-metrics-box" id="box-3">
        <p class="top-text"></p>
        <h2></h2>
        <p class="bottom-text"><span></span></p>
    </div>
</div>

<div class="edd-metrics-box" id="box-4">
    <div class="one-third">
        <p class="top-text">Last 30 days</p>
        <h2 class="detail-compare-first"></h2>
    </div>

    <div class="one-third">
        <p class="top-text">6 months ago</p>
        <h2 class="detail-compare-second"></h2>
    </div>

    <div class="one-third last-col">
        <p class="top-text">12 months ago</p>
        <h2 class="detail-compare-third"></h2>
    </div>
</div>

<?php do_action('edd_metrics_after_detail'); ?>