<div class="daterange daterange--double metrics-datepicker"></div>

<h2 class="page-title">Metrics Overview</h2>

<!-- <div class="edd-metrics-box edd-metrics-chart-wrapper">
    <canvas id="metrics-chart" width="400" height="150"></canvas>
</div> -->

<section class="two-thirds">

	<?php do_action('edd_metrics_before_boxes'); ?>

	<div class="one-half">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Revenue', 'edd-metrics'); ?></p>
	        <h2 id="revenue"></h2>
	        <p class="bottom-text" id="revenue-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half last-col">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Sales', 'edd-metrics'); ?></p>
	        <h2 id="sales"></h2>
	        <p class="bottom-text" id="sales-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Avg. Per Customer', 'edd-metrics'); ?></p>
	        <h2 id="avgpercust"></h2>
	        <p class="bottom-text" id="avgpercust-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half last-col">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Est. Yearly Revenue', 'edd-metrics'); ?></p>
	        <h2 id="yearly"></h2>
	        <p class="bottom-text" id="avgyearly-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Refunds', 'edd-metrics'); ?></p>
	        <h2 id="refunds" class="metrics-title1"></h2>
	        <h2 id="refund-amount" class="metrics-title2"></h2>
	        <p class="bottom-text" id="refunds-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half last-col">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Renewals', 'edd-metrics'); ?></p>
	        <h2 id="renewals" class="metrics-title1"></h2>
	        <h2 id="renewal-amount" class="metrics-title2"></h2>
	        <p class="bottom-text" id="renewals-compare"><span></span></p>
	    </div>
	</div>

	<?php do_action('edd_metrics_after_boxes'); ?>

</section>

<div class="one-third last-col">

	<a href="<?php echo admin_url(); ?>edit.php?post_type=download&page=edd_metrics&view=metrics-details&metric=revenue" class="metrics-details-link">View Revenue Details &rarr;</a>

    <?php do_action('edd_metrics_dash_sidebar'); ?>
    
</div>