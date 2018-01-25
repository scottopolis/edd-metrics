=== EDD Metrics ===

Contributors: scottopolis, growdev, netzberufler
Tags: easy digital downloads, edd, analytics, metrics, statistics, baremetrics
Requires at least: 4.0
Tested up to: 4.9.2
Stable tag: 0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Better reports for Easy Digital Downloads, similar to Baremetrics.

== Description ==

Get the important metrics for your business, such as average revenue per customer, renewal rate, refund rate, and more. Support EDD Software Licensing add-on for renewal rates, and EDD Recurring Payments for subscription information.

Included metrics:

* Net Revenue (Properly subtracts refunds)
* Sales
* Average revenue per customer
* Estimated monthly revenue
* Refunds
* Discounts
* Renewals, and renewal rate (if EDD Software Licensing is active)
* Subscriptions (if EDD Recurring Payments is active)
* Recurring revenue this period and next 30 days
* Earnings by download
* Earnings by gateway
* New customers

Charts are displayed on the detail page along with other metrics.

To contribute or report an issue, please use the [EDD Metrics Github](https://github.com/scottopolis/edd-metrics)

This plugin is inspired by (and basically a total copy of) [Baremetrics](https://baremetrics.com/). I would have just used Baremetrics instead of building a new plugin, except that Baremetrics is very specific to SaaS businesses, and doesn't really work for EDD.

== Installation ==

First, make sure Easy Digital Downloads is active. Next, install and activate this plugin on your WordPress site, then visit the "Metrics" menu under the "Downloads" left menu item.

Change the date using the datepicker in the top right hand corner. Click "Revenue Details" to see charts and more metrics.

== Screenshots ==

1. Revenue dashboard

2. Revenue details

== Changelog ==

=0.7=

* Fix net revenue stats for some historical periods - props to @danlester

=0.6=

* Add recurring revenue metrics
* Change revenue total to net revenue, which accounts for refunds EDD does not track properly

= 0.5.1 =

* Fix previous year chart data

= 0.5.0 =

* Support for commissions
* Fix PHP Warnings
* Fix JS errors

= 0.4.0 =

* Initial release