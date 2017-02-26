<div class="daterange daterange--double metrics-datepicker"></div>

<h2 class="page-title"><?php _e('Metrics Overview', 'edd-metrics'); ?></h2>

<!-- <div class="edd-metrics-box edd-metrics-chart-wrapper">
    <canvas id="metrics-chart" width="400" height="150"></canvas>
</div> -->

<section class="two-thirds">

	<?php do_action('edd_metrics_before_boxes'); ?>

	<div class="one-half">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Net Revenue', 'edd-metrics'); ?></p>
	        <h2 id="revenue"></h2>
	        <p class="bottom-text" id="revenue-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half last-col">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Revenue Today', 'edd-metrics'); ?></p>
	        <h2 id="earnings-today"></h2>
	        <!-- <p class="bottom-text" id="sales-compare"><span></span></p> -->
	    </div>
	</div>

	<div class="one-half">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Sales', 'edd-metrics'); ?></p>
	        <h2 id="sales"></h2>
	        <p class="bottom-text" id="sales-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half last-col">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Avg. Per Customer', 'edd-metrics'); ?></p>
	        <h2 id="avgpercust"></h2>
	        <p class="bottom-text" id="avgpercust-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Refunds', 'edd-metrics'); ?></p>
	        <h2 id="refund-amount" class="metrics-title1"></h2>
	        <h2 id="refunds" class="metrics-title2"></h2>
	        <p class="bottom-text" id="refunds-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half last-col">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Discounts', 'edd-metrics'); ?></p>
	        <h2 id="discounts" class="metrics-title1"></h2>
	        <h2 id="discounts-count" class="metrics-title2"></h2>
	        <p class="bottom-text" id="discounts-compare"><span></span></p>
	    </div>
	</div>

	<?php if( class_exists('EDD_Recurring') ) : ?>
	<div class="one-half">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Recurring Revenue', 'edd-metrics'); ?></p>
	        <h2 id="recurring-revenue"></h2>
	        <p class="bottom-text" id="recurring-compare"><span></span></p> 
	    </div>
	</div>

	<div class="one-half last-col">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('New Subscriptions', 'edd-metrics'); ?></p>
	        <h2 id="subscriptions"></h2>
	        <p class="bottom-text" id="subscriptions-compare"><span></span></p>
	    </div>
	</div>

	<div class="one-half">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Recurring Next 30 Days', 'edd-metrics'); ?></p>
	        <h2 id="recurring-revenue-30"></h2>
	        <!-- <p class="bottom-text" id="subscriptions-compare"><span></span></p> -->
	    </div>
	</div>
	<?php endif; ?>

	<?php if( class_exists('EDD_Software_Licensing') ) : ?>
	<div class="one-half last-col">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('License Renewals', 'edd-metrics'); ?></p>
	        <h2 id="renewal-amount" class="metrics-title1"></h2>
	        <h2 id="renewals" class="metrics-title2"></h2>
	        <p class="bottom-text" id="renewals-compare"><span></span></p>
	    </div>
	</div>
	<?php endif; ?>

	<?php if( defined( 'EDD_COMMISSIONS_VERSION' ) ): ?>
		<div class="one-half last-col">
			<div class="edd-metrics-box">
				<p class="top-text"><?php _e('Unpaid Commissions', 'edd-metrics'); ?></p>
				<h2 id="commissions-amount" class="metrics-title1"></h2>
				<h2 id="commissions" class="metrics-title2"></h2>
			</div>
		</div>
	<?php endif; ?>

	<!--  	<div class="one-half last-col">
	    <div class="edd-metrics-box">
	        <p class="top-text"><?php _e('Est. Yearly Revenue', 'edd-metrics'); ?></p>
	        <h2 id="yearly"></h2>
	        <p class="bottom-text" id="avgyearly-compare"><span></span></p>
	    </div>
	</div> -->

	<?php do_action('edd_metrics_after_boxes'); ?>

</section>

<div class="one-third last-col">

	<div class="metrics-details-link">

	<a href="<?php echo admin_url(); ?>edit.php?post_type=download&page=edd_metrics&view=metrics-details&metric=revenue"><img src="<?php echo plugins_url( 'assets/img/chart1.png', dirname(__FILE__) ); ?>" class="details-chart" />
	<br>
	<?php _e('View Revenue Details', 'edd-metrics'); ?> &rarr;</a>

	</div>

    <?php do_action('edd_metrics_dash_sidebar'); ?>
    
</div>