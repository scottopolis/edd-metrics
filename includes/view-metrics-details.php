<div class="daterange daterange--double metrics-datepicker metrics-detail"></div>

<h2 class="page-title"><?php _e('Revenue Detail', 'edd-metrics'); ?></h2>

<a href="<?php echo admin_url(); ?>edit.php?post_type=download&page=edd_metrics" class="button button-primary" style="float:right;">&larr; <?php _e('Back to Overview', 'edd-metrics'); ?></a>

<?php do_action('edd_metrics_before_detail'); ?>

<h2 id="revenue" class="metrics-big-title"></h2>

<div class="edd-metrics-boxes edd-metrics-boxes-full">

    <div class="edd-metrics-flex" style="margin-bottom:10px">

        <div class="edd-metrics-box edd-metrics-box-full edd-metrics-chart-wrapper">
            <canvas id="metrics-line-chart" width="400" height="125"></canvas>
        </div>

    </div>

    <div class="edd-metrics-flex edd-metrics-flex-combined" style="margin-bottom:25px">

        <div class="edd-metrics-box" id="box-4">
            <p class="top-text"><?php _e('Previous period', 'edd-metrics'); ?></p>
            <h2 class="detail-compare-first"></h2>
            <p class="bottom-text" id="revenue-compare"><span></span></p>
        </div>

        <div class="edd-metrics-box" id="box-5">
            <p class="top-text"><?php _e('6 months ago', 'edd-metrics'); ?></p>
            <h2 class="detail-compare-second"></h2>
            <p class="bottom-text" id="revenue-6mocompare"><span></span></p>
        </div>

        <div class="edd-metrics-box" id="box-6">
            <p class="top-text"><?php _e('12 months ago', 'edd-metrics'); ?></p>
            <h2 class="detail-compare-third"></h2>
            <p class="bottom-text" id="revenue-12mocompare"><span></span></p>
        </div>

    </div>

    <div class="edd-metrics-flex edd-metrics-flex-combined" style="margin-bottom:25px">

        <div class="edd-metrics-box" id="earnings-today">
            <p class="top-text"><?php _e('Today', 'edd-metrics'); ?></p>
            <h2></h2>
            <p class="bottom-text" id=""><span></span></p>
        </div>

        <div class="edd-metrics-box" id="earnings-this-month">
            <p class="top-text"><?php _e('This Month', 'edd-metrics'); ?></p>
            <h2></h2>
            <p class="bottom-text" id=""><span></span></p>
        </div>

        <div class="edd-metrics-box" id="monthly">
            <p class="top-text"><?php _e('Est. Monthly Revenue', 'edd-metrics'); ?></p>
            <h2></h2>
            <p class="bottom-text" id=""><span></span></p>
        </div>

    </div>

</div>

<div class="edd-metrics-boxes edd-metrics-boxes-full">

    <div class="edd-metrics-flex edd-metrics-flex-half" style="margin-bottom:5px">

        <div class="edd-metrics-box" id="new-customers">
            <p class="top-text"><?php _e('New Customers', 'edd-metrics'); ?></p>
            <h2></h2>
            <p class="bottom-text" id=""><span></span></p>
        </div>

        <?php if( class_exists('EDD_Software_Licensing') ) : ?>
        <div class="edd-metrics-box" id="renewal-rate">
            <p class="top-text"><?php _e('Renewal Rate', 'edd-metrics'); ?></p>
            <h2></h2>
            <p class="bottom-text" id="yearly-renewal-compare"><span></span></p>
        </div>
        <?php endif; ?>

    </div>

    <div class="edd-metrics-flex edd-metrics-flex-half">

        <div class="edd-metrics-box edd-metrics-chart-wrapper one-half">
            <h3><?php _e('Earnings by download', 'edd-metrics'); ?></h3>
            <canvas id="metrics-piechart-by-download" width="200" height="200"></canvas>
        </div>

        <div class="edd-metrics-box edd-metrics-chart-wrapper one-half last-col">
            <h3><?php _e('Earnings by Gateway', 'edd-metrics'); ?></h3>
            <canvas id="metrics-piechart-by-gateway" width="200" height="200"></canvas>
        </div>

    </div>

</div>

<?php do_action('edd_metrics_after_detail'); ?>