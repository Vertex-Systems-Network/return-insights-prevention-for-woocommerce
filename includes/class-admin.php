<?php
/**
 * WooCommerce admin navigation and React application bootstrap.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;
defined( 'ABSPATH' ) || exit;

/** Registers the Return Insights page and its scoped no-build assets. */
final class Admin {
	private const PAGE_SLUG = 'wpessential-return-insights-prevention-woocommerce';
	private static string $page_hook = '';

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ), 60 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	public static function add_menu(): void {
		self::$page_hook = (string) add_submenu_page(
			'woocommerce',
			__( 'Return Insights', WPE_RI_TEXT_DOMAIN ),
			__( 'Return Insights', WPE_RI_TEXT_DOMAIN ),
			Capabilities::VIEW,
			self::PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	/** Load the React app only on this plugin screen. */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( ! self::$page_hook || self::$page_hook !== $hook_suffix || ! current_user_can( Capabilities::VIEW ) ) {
			return;
		}
		$script_path = WPE_RI_PATH . 'assets/js/admin.js';
		$style_path = WPE_RI_PATH . 'assets/css/admin.css';
		wp_enqueue_style(
			'wpe-ri-admin',
			WPE_RI_URL . 'assets/css/admin.css',
			array(),
			file_exists( $style_path ) ? (string) filemtime( $style_path ) : WPE_RI_VERSION
		);
		wp_enqueue_script(
			'wpe-ri-admin',
			WPE_RI_URL . 'assets/js/admin.js',
			array( 'wp-element', 'wp-components', 'wp-api-fetch' ),
			file_exists( $script_path ) ? (string) filemtime( $script_path ) : WPE_RI_VERSION,
			true
		);
		wp_localize_script(
			'wpe-ri-admin',
			'WPEssentialReturnInsights',
			array(
				'rootId' => 'wpe-ri-app',
				'namespace' => '/wpessential/return-insights/v1',
				'today' => current_datetime()->format( 'Y-m-d' ),
				'defaultFrom' => current_datetime()->modify( '-29 days' )->format( 'Y-m-d' ),
				'canManage' => current_user_can( Capabilities::MANAGE ),
				'proEnabled' => Pattern_Service::is_entitled(),
				'strings' => self::strings(),
			)
		);
	}

	/** Render the React root after enforcing the page capability. */
	public static function render_page(): void {
		if ( ! current_user_can( Capabilities::VIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to view return insights.', WPE_RI_TEXT_DOMAIN ) );
		}
		echo '<div class="wrap wpe-ri-admin-wrap"><div id="wpe-ri-app" aria-live="polite">';
		echo '<p>' . esc_html__( 'Loading Return Insights…', WPE_RI_TEXT_DOMAIN ) . '</p>';
		echo '</div><noscript><p>' . esc_html__( 'JavaScript is required to use the Return Insights screen.', WPE_RI_TEXT_DOMAIN ) . '</p></noscript></div>';
	}

	/** Translation strings passed to the browser-ready React application. */
	private static function strings(): array {
		return array(
			'brand' => __( 'WPEssential · WooCommerce', WPE_RI_TEXT_DOMAIN ),
			'title' => __( 'Return Insights', WPE_RI_TEXT_DOMAIN ),
			'subtitle' => __( 'Understand return reasons, record item-level evidence, and review what may need attention.', WPE_RI_TEXT_DOMAIN ),
			'navigation' => __( 'Return Insights sections', WPE_RI_TEXT_DOMAIN ),
			'overviewTab' => __( 'Overview', WPE_RI_TEXT_DOMAIN ),
			'historyTab' => __( 'Insights & event history', WPE_RI_TEXT_DOMAIN ),
			'captureTab' => __( 'Capture insight', WPE_RI_TEXT_DOMAIN ),
			'sourcesTab' => __( 'Capture sources', WPE_RI_TEXT_DOMAIN ),
			'catalogTab' => __( 'Reason catalog', WPE_RI_TEXT_DOMAIN ),
			'patternsTab' => __( 'Product trends · Pro', WPE_RI_TEXT_DOMAIN ),
			'patternsTitle' => __( 'Product and variation insights', WPE_RI_TEXT_DOMAIN ),
			'patternsIntro' => __( 'Compare confirmed item returns with eligible sold quantities in mature purchase cohorts.', WPE_RI_TEXT_DOMAIN ),
			'proLockedTitle' => __( 'Available with the Pro add-on', WPE_RI_TEXT_DOMAIN ),
			'proLockedIntro' => __( 'The Free edition records evidence and provides reason counts. Pro adds mature product/variation cohort comparisons.', WPE_RI_TEXT_DOMAIN ),
			'maturityDays' => __( 'Maturity window', WPE_RI_TEXT_DOMAIN ),
			'cohortDays' => __( 'Cohort length', WPE_RI_TEXT_DOMAIN ),
			'dayCount' => __( '%d days', WPE_RI_TEXT_DOMAIN ),
			'updatePatterns' => __( 'Update comparison', WPE_RI_TEXT_DOMAIN ),
			'currentCohort' => __( 'Current mature cohort', WPE_RI_TEXT_DOMAIN ),
			'comparisonCohort' => __( 'Previous cohort', WPE_RI_TEXT_DOMAIN ),
			'currentRate' => __( 'Current rate', WPE_RI_TEXT_DOMAIN ),
			'comparisonRate' => __( 'Previous rate', WPE_RI_TEXT_DOMAIN ),
			'eligibleSold' => __( 'Returned / eligible sold', WPE_RI_TEXT_DOMAIN ),
			'rateChange' => __( 'Change (percentage points)', WPE_RI_TEXT_DOMAIN ),
			'lowSample' => __( 'Low sample', WPE_RI_TEXT_DOMAIN ),
			'lowSampleRateSuppressed' => __( 'Rate suppressed: fewer than 20 eligible sold units in this cohort.', WPE_RI_TEXT_DOMAIN ),
			'incompleteCoverage' => __( 'Coverage is incomplete; rates are suppressed for this cohort.', WPE_RI_TEXT_DOMAIN ),
			'orderScanLimit' => __( 'This cohort exceeds the bounded order scan. No partial rate is displayed.', WPE_RI_TEXT_DOMAIN ),
			'rateUnavailable' => __( 'Unavailable', WPE_RI_TEXT_DOMAIN ),
			'insufficientDenominator' => __( 'No eligible sold units in this cohort.', WPE_RI_TEXT_DOMAIN ),
			'returnExceedsSold' => __( 'Return quantity exceeds the cohort sales denominator; review source records.', WPE_RI_TEXT_DOMAIN ),
			'noPatternCoverage' => __( 'Patterns are hidden because this cohort does not have complete mapping/order coverage.', WPE_RI_TEXT_DOMAIN ),
			'patternBoundary' => __( 'Only completed orders with a completion date contribute sold quantity. Refund-only observations are excluded from confirmed-return rates. Rates describe recorded evidence, not causation; partial fulfillment data from other extensions is not inferred.', WPE_RI_TEXT_DOMAIN ),
			'noPatterns' => __( 'No confirmed return patterns in these cohorts', WPE_RI_TEXT_DOMAIN ),
			'noPatternsHelp' => __( 'Try a longer cohort window or continue recording item-level confirmed returns. Refund observations alone do not create product patterns.', WPE_RI_TEXT_DOMAIN ),
			'loadPatternsError' => __( 'Could not load product and variation insights.', WPE_RI_TEXT_DOMAIN ),
			'reportFrom' => __( 'From date', WPE_RI_TEXT_DOMAIN ),
			'reportTo' => __( 'To date', WPE_RI_TEXT_DOMAIN ),
			'runReport' => __( 'Update report', WPE_RI_TEXT_DOMAIN ),
			'periodLabel' => __( 'Reporting period', WPE_RI_TEXT_DOMAIN ),
			'totalQuantity' => __( 'Recorded quantity', WPE_RI_TEXT_DOMAIN ),
			'quantityHelp' => __( 'Sum of captured event quantities; not a return rate.', WPE_RI_TEXT_DOMAIN ),
			'eventCountHelp' => __( 'Captured event records in the selected period.', WPE_RI_TEXT_DOMAIN ),
			'mappingCoverage' => __( 'Reason mapping coverage', WPE_RI_TEXT_DOMAIN ),
			'mappedEvents' => __( 'mapped records', WPE_RI_TEXT_DOMAIN ),
			'noMappingData' => __( 'No records to measure yet.', WPE_RI_TEXT_DOMAIN ),
			'reportBoundary' => __( 'Counts describe captured evidence only. Refund observations are not confirmed physical returns; no return rate or cause is inferred. Tables show the top 20 reasons and top 10 products/categories; category memberships may overlap.', WPE_RI_TEXT_DOMAIN ),
			'reasonBreakdown' => __( 'Reasons recorded', WPE_RI_TEXT_DOMAIN ),
			'sampleSize' => __( 'Records (sample)', WPE_RI_TEXT_DOMAIN ),
			'eventCount' => __( 'Records', WPE_RI_TEXT_DOMAIN ),
			'eventKindBreakdown' => __( 'Evidence types', WPE_RI_TEXT_DOMAIN ),
			'sourceBreakdown' => __( 'Capture sources', WPE_RI_TEXT_DOMAIN ),
			'captureSource' => __( 'Source', WPE_RI_TEXT_DOMAIN ),
			'productBreakdown' => __( 'Products with most records', WPE_RI_TEXT_DOMAIN ),
			'categoryBreakdown' => __( 'Product categories', WPE_RI_TEXT_DOMAIN ),
			'product' => __( 'Product / variation', WPE_RI_TEXT_DOMAIN ),
			'category' => __( 'Category', WPE_RI_TEXT_DOMAIN ),
			'noReportRows' => __( 'No records in this group for the selected period.', WPE_RI_TEXT_DOMAIN ),
			'noReportData' => __( 'No evidence in this date range', WPE_RI_TEXT_DOMAIN ),
			'noReportDataHelp' => __( 'Try a wider date range or record an item-level insight. No metrics are estimated when the period is empty.', WPE_RI_TEXT_DOMAIN ),
			'loadReportError' => __( 'Could not load the free return insight report.', WPE_RI_TEXT_DOMAIN ),
			'eventKinds' => array(
				'confirmed_return' => __( 'Confirmed return (manual)', WPE_RI_TEXT_DOMAIN ),
				'refund_only' => __( 'Refund observation', WPE_RI_TEXT_DOMAIN ),
				'exchange' => __( 'Exchange (manual)', WPE_RI_TEXT_DOMAIN ),
			),
			'overviewEyebrow' => __( 'Evidence at a glance', WPE_RI_TEXT_DOMAIN ),
			'overviewTitle' => __( 'Your return evidence', WPE_RI_TEXT_DOMAIN ),
			'overviewIntro' => __( 'See how much insight data is available and where it came from.', WPE_RI_TEXT_DOMAIN ),
			'totalInsights' => __( 'Active insight records', WPE_RI_TEXT_DOMAIN ),
			'manualInsights' => __( 'Manual item insights', WPE_RI_TEXT_DOMAIN ),
			'refundObservations' => __( 'Refund observations', WPE_RI_TEXT_DOMAIN ),
			'recordCountNote' => __( 'Counts only. These reports do not calculate return rates or prove why an event occurred.', WPE_RI_TEXT_DOMAIN ),
			'reportsComing' => __( 'Basic reason reports are available for the selected date period.', WPE_RI_TEXT_DOMAIN ),
			'insightStart' => __( 'Record an insight', WPE_RI_TEXT_DOMAIN ),
			'sourcesEyebrow' => __( 'Where records come from', WPE_RI_TEXT_DOMAIN ),
			'sourcesTitle' => __( 'Capture sources', WPE_RI_TEXT_DOMAIN ),
			'sourcesIntro' => __( 'Manual item records and native WooCommerce refunds are stored separately with clear provenance.', WPE_RI_TEXT_DOMAIN ),
			'manualSourceTitle' => __( 'Manual item capture', WPE_RI_TEXT_DOMAIN ),
			'refundSourceTitle' => __( 'WooCommerce refund observation', WPE_RI_TEXT_DOMAIN ),
			'manualSource' => __( 'A store user records a confirmed return or exchange against one order item.', WPE_RI_TEXT_DOMAIN ),
			'refundSource' => __( 'A WooCommerce refund is observed at refund scope. It does not confirm that a product physically returned.', WPE_RI_TEXT_DOMAIN ),
			'otherSource' => __( 'A plugin source record.', WPE_RI_TEXT_DOMAIN ),
			'lastSeen' => __( 'Latest record date (store timezone)', WPE_RI_TEXT_DOMAIN ),
			'recordCount' => __( 'records', WPE_RI_TEXT_DOMAIN ),
			'noSourceEvents' => __( 'No capture records yet', WPE_RI_TEXT_DOMAIN ),
			'noSourceEventsHelp' => __( 'Manual insights appear here after they are recorded. Refund observations appear when WooCommerce refunds are created.', WPE_RI_TEXT_DOMAIN ),
			'sourcePrivacy' => __( 'Source summaries contain counts and dates only. Customer details and refund text are not displayed.', WPE_RI_TEXT_DOMAIN ),
			'loadSourcesError' => __( 'Could not load capture source information.', WPE_RI_TEXT_DOMAIN ),
			'captureEyebrow' => __( 'Per-item record', WPE_RI_TEXT_DOMAIN ),
			'newCapture' => __( 'Record a return insight', WPE_RI_TEXT_DOMAIN ),
			'editCapture' => __( 'Edit return insight', WPE_RI_TEXT_DOMAIN ),
			'captureIntro' => __( 'This records insight for analysis. It does not create an RMA, issue a refund, or change the WooCommerce order.', WPE_RI_TEXT_DOMAIN ),
			'readOnlyNotice' => __( 'You have read-only access. Ask a store administrator for permission to add or manage insights.', WPE_RI_TEXT_DOMAIN ),
			'orderNumber' => __( 'WooCommerce order ID', WPE_RI_TEXT_DOMAIN ),
			'orderLookupHint' => __( 'Enter the numeric order ID. Customer details are not shown here.', WPE_RI_TEXT_DOMAIN ),
			'loadOrder' => __( 'Find order items', WPE_RI_TEXT_DOMAIN ),
			'lineItems' => __( 'items', WPE_RI_TEXT_DOMAIN ),
			'noOrderItems' => __( 'No product line items were found for this order.', WPE_RI_TEXT_DOMAIN ),
			'loadOrderError' => __( 'Could not load this order. Check the ID and your access.', WPE_RI_TEXT_DOMAIN ),
			'orderIdRequired' => __( 'Enter a valid WooCommerce order ID first.', WPE_RI_TEXT_DOMAIN ),
			'productItem' => __( 'Product or variation', WPE_RI_TEXT_DOMAIN ),
			'reason' => __( 'Return reason', WPE_RI_TEXT_DOMAIN ),
			'chooseReason' => __( 'Choose a reason', WPE_RI_TEXT_DOMAIN ),
			'eventType' => __( 'Record type', WPE_RI_TEXT_DOMAIN ),
			'confirmedReturn' => __( 'Confirmed return', WPE_RI_TEXT_DOMAIN ),
			'exchange' => __( 'Exchange', WPE_RI_TEXT_DOMAIN ),
			'quantity' => __( 'Quantity', WPE_RI_TEXT_DOMAIN ),
			'quantityHint' => __( 'Ordered quantity: %s. The saved total cannot exceed this amount.', WPE_RI_TEXT_DOMAIN ),
			'ordered' => __( 'ordered', WPE_RI_TEXT_DOMAIN ),
			'occurredDate' => __( 'Return or exchange date', WPE_RI_TEXT_DOMAIN ),
			'siteDateHint' => __( 'Dates are saved using the store timezone.', WPE_RI_TEXT_DOMAIN ),
			'updateInsight' => __( 'Save changes', WPE_RI_TEXT_DOMAIN ),
			'saveInsight' => __( 'Save return insight', WPE_RI_TEXT_DOMAIN ),
			'cancelEdit' => __( 'Cancel editing', WPE_RI_TEXT_DOMAIN ),
			'editScope' => __( 'Order and item links stay fixed when editing a record.', WPE_RI_TEXT_DOMAIN ),
			'completeRequired' => __( 'Load an order, choose an item and reason, and complete the required fields.', WPE_RI_TEXT_DOMAIN ),
			'eventSaved' => __( 'Return insight saved.', WPE_RI_TEXT_DOMAIN ),
			'eventUpdated' => __( 'Return insight updated.', WPE_RI_TEXT_DOMAIN ),
			'eventDeleted' => __( 'Return insight removed from active reports.', WPE_RI_TEXT_DOMAIN ),
			'saveError' => __( 'The insight could not be saved. Review the fields and try again.', WPE_RI_TEXT_DOMAIN ),
			'deleteError' => __( 'The insight could not be removed.', WPE_RI_TEXT_DOMAIN ),
			'deleteReason' => __( 'Why are you removing this record? (required)', WPE_RI_TEXT_DOMAIN ),
			'confirmDelete' => __( 'Confirm removal', WPE_RI_TEXT_DOMAIN ),
			'cancelDelete' => __( 'Keep record', WPE_RI_TEXT_DOMAIN ),
			'historyEyebrow' => __( 'Stored evidence', WPE_RI_TEXT_DOMAIN ),
			'eventHistory' => __( 'Return insight history', WPE_RI_TEXT_DOMAIN ),
			'historyIntro' => __( 'Review manual insights and observed WooCommerce refunds. Refund records are not proof of a physical return.', WPE_RI_TEXT_DOMAIN ),
			'refresh' => __( 'Refresh', WPE_RI_TEXT_DOMAIN ),
			'filterOrder' => __( 'Filter by order ID', WPE_RI_TEXT_DOMAIN ),
			'filterHint' => __( 'Leave empty to view all recent records.', WPE_RI_TEXT_DOMAIN ),
			'applyFilter' => __( 'Apply filter', WPE_RI_TEXT_DOMAIN ),
			'clearFilter' => __( 'Clear', WPE_RI_TEXT_DOMAIN ),
			'loading' => __( 'Loading…', WPE_RI_TEXT_DOMAIN ),
			'loadEventsError' => __( 'Could not load return insight history.', WPE_RI_TEXT_DOMAIN ),
			'date' => __( 'Date', WPE_RI_TEXT_DOMAIN ),
			'order' => __( 'Order', WPE_RI_TEXT_DOMAIN ),
			'item' => __( 'Item / scope', WPE_RI_TEXT_DOMAIN ),
			'type' => __( 'Type', WPE_RI_TEXT_DOMAIN ),
			'actions' => __( 'Actions', WPE_RI_TEXT_DOMAIN ),
			'refundScope' => __( 'Refund-level (unassigned)', WPE_RI_TEXT_DOMAIN ),
			'refundOnly' => __( 'Refund observation', WPE_RI_TEXT_DOMAIN ),
			'unknown' => __( 'Unknown', WPE_RI_TEXT_DOMAIN ),
			'page' => __( 'Page %1$s of %2$s', WPE_RI_TEXT_DOMAIN ),
			'previous' => __( 'Previous', WPE_RI_TEXT_DOMAIN ),
			'next' => __( 'Next', WPE_RI_TEXT_DOMAIN ),
			'noEvents' => __( 'No return insights yet', WPE_RI_TEXT_DOMAIN ),
			'noEventsHelp' => __( 'Record an item-level return insight or let the store’s WooCommerce refunds be observed. Refunds remain clearly labeled as refund evidence.', WPE_RI_TEXT_DOMAIN ),
			'catalogEyebrow' => __( 'Stable IDs · editable labels', WPE_RI_TEXT_DOMAIN ),
			'reasonCatalog' => __( 'Reason catalog', WPE_RI_TEXT_DOMAIN ),
			'catalogIntro' => __( 'Reason IDs stay stable so changing a label does not rewrite historical insight records.', WPE_RI_TEXT_DOMAIN ),
			'addReason' => __( 'Add a custom reason', WPE_RI_TEXT_DOMAIN ),
			'editReason' => __( 'Edit reason labels', WPE_RI_TEXT_DOMAIN ),
			'reasonLabel' => __( 'Display label', WPE_RI_TEXT_DOMAIN ),
			'reasonGroup' => __( 'Group', WPE_RI_TEXT_DOMAIN ),
			'group' => __( 'Group', WPE_RI_TEXT_DOMAIN ),
			'groups' => array(
				'product' => __( 'Product', WPE_RI_TEXT_DOMAIN ),
				'fulfillment' => __( 'Fulfillment', WPE_RI_TEXT_DOMAIN ),
				'other' => __( 'Other', WPE_RI_TEXT_DOMAIN ),
			),
			'translatedLabels' => __( 'Translated labels', WPE_RI_TEXT_DOMAIN ),
			'translationsHint' => __( 'Use WordPress locale codes such as fr_FR or ur_PK. Leave blank when not needed.', WPE_RI_TEXT_DOMAIN ),
			'locale' => __( 'Locale', WPE_RI_TEXT_DOMAIN ),
			'translatedLabel' => __( 'Translated label', WPE_RI_TEXT_DOMAIN ),
			'addLanguage' => __( 'Add translation', WPE_RI_TEXT_DOMAIN ),
			'removeLanguage' => __( 'Remove', WPE_RI_TEXT_DOMAIN ),
			'noTranslations' => __( 'No translated labels', WPE_RI_TEXT_DOMAIN ),
			'status' => __( 'Status', WPE_RI_TEXT_DOMAIN ),
			'active' => __( 'Active', WPE_RI_TEXT_DOMAIN ),
			'inactive' => __( 'Inactive', WPE_RI_TEXT_DOMAIN ),
			'edit' => __( 'Edit', WPE_RI_TEXT_DOMAIN ),
			'delete' => __( 'Remove', WPE_RI_TEXT_DOMAIN ),
			'activate' => __( 'Activate', WPE_RI_TEXT_DOMAIN ),
			'deactivate' => __( 'Deactivate', WPE_RI_TEXT_DOMAIN ),
			'createReason' => __( 'Add reason', WPE_RI_TEXT_DOMAIN ),
			'updateReason' => __( 'Save reason', WPE_RI_TEXT_DOMAIN ),
			'reasonCreated' => __( 'Reason added to the catalog.', WPE_RI_TEXT_DOMAIN ),
			'reasonUpdated' => __( 'Reason labels updated.', WPE_RI_TEXT_DOMAIN ),
			'reasonEnabled' => __( 'Reason activated.', WPE_RI_TEXT_DOMAIN ),
			'reasonDisabled' => __( 'Reason deactivated. Existing records keep their stable reason ID.', WPE_RI_TEXT_DOMAIN ),
			'reasonSaveError' => __( 'The reason could not be saved. Check that the label and locale codes are valid.', WPE_RI_TEXT_DOMAIN ),
			'loadReasonsError' => __( 'Could not load the reason catalog.', WPE_RI_TEXT_DOMAIN ),
			'footer' => __( 'Return insights support prevention decisions. They do not process returns or establish causation.', WPE_RI_TEXT_DOMAIN ),
		);
	}
}
