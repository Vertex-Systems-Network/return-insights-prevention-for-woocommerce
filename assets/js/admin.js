( function ( wp, config ) {
	'use strict';

	if ( ! wp || ! wp.element || ! wp.apiFetch || ! config ) {
		return;
	}

	const h = wp.element.createElement;
	const useEffect = wp.element.useEffect;
	const useState = wp.element.useState;
	const apiFetch = wp.apiFetch;
	const components = wp.components || {};
	const Button = components.Button || 'button';
	const Notice = components.Notice || 'div';
	const Spinner = components.Spinner || 'span';
	const namespace = config.namespace;

	function makeKey() {
		if ( window.crypto && window.crypto.randomUUID ) {
			return window.crypto.randomUUID();
		}
		return 'manual-' + Date.now() + '-' + Math.random().toString( 36 ).slice( 2, 12 );
	}

	function App() {
		const [ tab, setTab ] = useState( 'capture' );
		const [ notice, setNotice ] = useState( null );
		const [ reasons, setReasons ] = useState( [] );
		const [ events, setEvents ] = useState( [] );
		const [ report, setReport ] = useState( null );
		const [ reportBusy, setReportBusy ] = useState( false );
		const [ reportFrom, setReportFrom ] = useState( config.defaultFrom );
		const [ reportTo, setReportTo ] = useState( config.today );
		const [ patternData, setPatternData ] = useState( null );
		const [ clusterData, setClusterData ] = useState( null );
		const [ alerts, setAlerts ] = useState( [] );
		const [ alertsBusy, setAlertsBusy ] = useState( false );
		const [ patternBusy, setPatternBusy ] = useState( false );
		const [ patternMaturity, setPatternMaturity ] = useState( '90' );
		const [ cohortDays, setCohortDays ] = useState( '90' );
		const [ sourceRows, setSourceRows ] = useState( [] );
		const [ sourceBusy, setSourceBusy ] = useState( false );
		const [ orderId, setOrderId ] = useState( '' );
		const [ historyOrderId, setHistoryOrderId ] = useState( '' );
		const [ order, setOrder ] = useState( null );
		const [ selectedItem, setSelectedItem ] = useState( '' );
		const [ eventKind, setEventKind ] = useState( 'confirmed_return' );
		const [ reasonId, setReasonId ] = useState( '' );
		const [ quantity, setQuantity ] = useState( '1' );
		const [ occurredDate, setOccurredDate ] = useState( config.today );
		const [ editing, setEditing ] = useState( null );
		const [ orderBusy, setOrderBusy ] = useState( false );
		const [ saving, setSaving ] = useState( false );
		const [ loadingEvents, setLoadingEvents ] = useState( false );
		const [ page, setPage ] = useState( 1 );
		const [ totalPages, setTotalPages ] = useState( 1 );
		const [ catalog, setCatalog ] = useState( [] );
		const [ catalogBusy, setCatalogBusy ] = useState( false );
		const [ reasonLabel, setReasonLabel ] = useState( '' );
		const [ reasonGroup, setReasonGroup ] = useState( 'product' );
		const [ translations, setTranslations ] = useState( [] );
		const [ editingReason, setEditingReason ] = useState( null );
		const [ deleteTarget, setDeleteTarget ] = useState( null );
		const [ deleteNote, setDeleteNote ] = useState( '' );

		function showError( error, fallback ) {
			setNotice( { status: 'error', message: error && error.message ? error.message : fallback } );
		}

		async function loadReasons() {
			try {
				const response = await apiFetch( { path: namespace + '/reasons?include_inactive=1' } );
				setReasons( ( response || [] ).filter( function ( item ) { return item.active; } ) );
				setCatalog( response || [] );
			} catch ( error ) {
				showError( error, config.strings.loadReasonsError );
			}
		}

		async function loadReport( from, to ) {
			setReportBusy( true );
			setReport( null );
			try {
				const params = new URLSearchParams();
				if ( from ) { params.set( 'date_from', from ); }
				if ( to ) { params.set( 'date_to', to ); }
				const response = await apiFetch( { path: namespace + '/summary?' + params.toString() } );
				setReport( response );
			} catch ( error ) {
				showError( error, config.strings.loadReportError );
			} finally {
				setReportBusy( false );
			}
		}

		async function loadPatterns( maturity, cohort ) {
			if ( ! config.proEnabled ) { return; }
			setPatternBusy( true );
			setPatternData( null );
			try {
				const params = new URLSearchParams();
				params.set( 'maturity_days', String( maturity || patternMaturity ) );
				params.set( 'cohort_days', String( cohort || cohortDays ) );
				const response = await apiFetch( { path: namespace + '/patterns?' + params.toString() } );
				setPatternData( response );
				const clusters = await apiFetch( { path: namespace + '/clusters?' + params.toString() } );
				setClusterData( clusters.clusters || [] );
			} catch ( error ) {
				showError( error, config.strings.loadPatternsError );
			} finally {
				setPatternBusy( false );
			}
		}

		async function loadAlerts() {
			if ( ! config.proEnabled ) { return; }
			setAlertsBusy( true );
			try {
				const response = await apiFetch( { path: namespace + '/alerts' } );
				setAlerts( response.alerts || [] );
			} catch ( error ) {
				showError( error, config.strings.loadAlertsError );
			} finally { setAlertsBusy( false ); }
		}

		async function setAlertStatus( alert, status ) {
			try {
				await apiFetch( { path: namespace + '/alerts/' + alert.alert_id, method: 'PATCH', data: { status: status, note: status === 'dismissed' ? config.strings.alertDismissAudit : '' } } );
				loadAlerts();
			} catch ( error ) { showError( error, config.strings.loadAlertsError ); }
		}

		function renderClusters() {
			const rows = clusterData || [];
			const tableRows = rows.map( function ( row ) {
				const controls = ( row.reason_ids || [] ).map( function ( id ) {
					const options = [ h( 'option', { key: 'default', value: '' }, config.strings.resetCluster ) ].concat(
						[ 'fit_size', 'product_condition', 'product_description', 'fulfillment', 'order_intent' ].map( function ( value ) {
							return h( 'option', { key: value, value: value }, value );
						} )
					);
					return h( 'label', { className: 'wpe-ri-cluster-control', key: id }, [
						id,
						h( 'select', {
							'aria-label': config.strings.assignCluster + ' ' + id,
						defaultValue: row.cluster_id,
						onChange: function ( event ) {
							apiFetch( { path: namespace + '/clusters/' + id, method: 'PATCH', data: { cluster_id: event.target.value } } )
								.then( function () { loadPatterns( patternMaturity, cohortDays ); } )
								.catch( function () { showError( null, config.strings.clusterUpdateError ); } );
						}
					}, options )
					] );
				} );
				return h( 'tr', { key: row.product_id + ':' + row.variation_id + ':' + row.cluster_id }, [
					h( 'td', null, '#' + row.product_id + ( row.variation_id ? ' / #' + row.variation_id : '' ) ),
					h( 'td', null, row.cluster_label ),
					h( 'td', null, ( row.reason_ids || [] ).join( ', ' ) ),
					h( 'td', null, null === row.current.rate_percent ? config.strings.rateUnavailable : row.current.rate_percent + '%' ),
					h( 'td', null, controls )
				] );
			} );
			return h( 'section', { className: 'wpe-ri-panel', 'aria-labelledby': 'wpe-ri-clusters-heading' }, [
				h( 'div', { className: 'wpe-ri-panel-heading' }, h( 'div', null, [
					h( 'h2', { id: 'wpe-ri-clusters-heading' }, config.strings.clustersTitle ),
					h( 'p', { className: 'wpe-ri-muted' }, config.strings.clustersIntro )
				] ) ),
				rows.length ? h( 'div', { className: 'wpe-ri-table-wrap' }, h( 'table', { className: 'wpe-ri-table' }, [
					h( 'thead', null, h( 'tr', null, [ config.strings.product, config.strings.cluster, config.strings.clusterMembers, config.strings.currentRate, config.strings.clusterActions ].map( function ( label ) { return h( 'th', { key: label }, label ); } ) ) ),
					h( 'tbody', null, tableRows )
				] ) ) : h( 'p', { className: 'wpe-ri-muted' }, config.strings.noClusters )
			] );
		}

		function renderAlerts() {
			if ( ! config.proEnabled ) { return h( 'section', { className: 'wpe-ri-panel' }, h( 'div', { className: 'wpe-ri-pro-lock', role: 'note' }, h( 'span', { className: 'wpe-ri-pro-lock-badge' }, 'PRO' ), h( 'h3', null, config.strings.proLockedTitle ), h( 'p', null, config.strings.proLockedIntro ) ) ); }
			return h( 'section', { className: 'wpe-ri-panel', 'aria-labelledby': 'wpe-ri-alerts-heading' },
				h( 'div', { className: 'wpe-ri-panel-heading' }, h( 'div', null, h( 'h2', { id: 'wpe-ri-alerts-heading' }, config.strings.alertsTitle ), h( 'p', { className: 'wpe-ri-muted' }, config.strings.alertsIntro ) ), h( Button, { onClick: loadAlerts, disabled: alertsBusy }, config.strings.refreshAlerts ) ),
				alertsBusy ? h( 'div', { className: 'wpe-ri-loading', role: 'status' }, h( Spinner ), h( 'span', null, config.strings.loading ) ) : alerts.length ? h( 'div', { className: 'wpe-ri-table-wrap' }, h( 'table', { className: 'wpe-ri-table' }, h( 'thead', null, h( 'tr', null, [ config.strings.product, config.strings.cluster, config.strings.alertEvidence, config.strings.alertStatus, config.strings.clusterActions ].map( function ( label ) { return h( 'th', { key: label }, label ); } ) ) ), h( 'tbody', null, alerts.map( function ( row ) { return h( 'tr', { key: row.alert_id }, h( 'td', null, '#' + row.product_id + ( row.variation_id ? ' / #' + row.variation_id : '' ) ), h( 'td', null, row.cluster_id ), h( 'td', null, row.current_returned_quantity + ' / ' + row.current_sold_quantity + ' · ' + row.current_rate_percent + '% · ' + row.mapping_percent + '% mapping' ), h( 'td', null, row.status ), h( 'td', null, h( Button, { onClick: function () { setAlertStatus( row, row.status === 'dismissed' ? 'active' : 'dismissed' ); } }, row.status === 'dismissed' ? config.strings.restoreAlert : config.strings.dismissAlert ) ) ); } ) ) ) ) : h( 'div', { className: 'wpe-ri-empty' }, h( 'h3', null, config.strings.noAlerts ), h( 'p', null, config.strings.noAlertsHelp ) )
			);
		}

		async function loadSources() {
			setSourceBusy( true );
			try {
				const response = await apiFetch( { path: namespace + '/sources' } );
				setSourceRows( response.sources || [] );
			} catch ( error ) {
				showError( error, config.strings.loadSourcesError );
			} finally {
				setSourceBusy( false );
			}
		}

		async function loadEvents( requestedPage, filterOrder ) {
			setLoadingEvents( true );
			try {
				const params = new URLSearchParams();
				params.set( 'page', String( requestedPage || 1 ) );
				params.set( 'per_page', '20' );
				if ( filterOrder ) {
					params.set( 'order_id', String( filterOrder ) );
				}
				const response = await apiFetch( { path: namespace + '/events?' + params.toString(), parse: false } );
				const list = await response.json();
				const count = Number( response.headers.get( 'X-WP-TotalPages' ) || 1 );
				setEvents( Array.isArray( list ) ? list : [] );
				setPage( requestedPage || 1 );
				setTotalPages( Math.max( 1, count ) );
			} catch ( error ) {
				showError( error, config.strings.loadEventsError );
			} finally {
				setLoadingEvents( false );
			}
		}

		useEffect( function () {
			loadReasons();
			loadEvents( 1, '' );
			loadReport( reportFrom, reportTo );
			loadSources();
		}, [] );

		async function loadOrder( id, targetEvent ) {
			const cleanId = String( id || '' ).trim();
			if ( ! /^\d+$/.test( cleanId ) || Number( cleanId ) < 1 ) {
				setNotice( { status: 'error', message: config.strings.orderIdRequired } );
				return;
			}
			setOrderBusy( true );
			setNotice( null );
			try {
				const found = await apiFetch( { path: namespace + '/orders/' + encodeURIComponent( cleanId ) } );
				setOrder( found );
				setOrderId( cleanId );
				if ( targetEvent ) {
					setSelectedItem( String( targetEvent.order_item_id ) );
					setEventKind( targetEvent.event_kind );
					setReasonId( targetEvent.reason_id );
					setQuantity( String( targetEvent.quantity ) );
					setOccurredDate( targetEvent.occurred_date || config.today );
				} else {
					setSelectedItem( found.items && found.items.length ? String( found.items[ 0 ].order_item_id ) : '' );
				}
				if ( !found.items || !found.items.length ) {
					setNotice( { status: 'warning', message: config.strings.noOrderItems } );
				}
			} catch ( error ) {
				setOrder( null );
				showError( error, config.strings.loadOrderError );
			} finally {
				setOrderBusy( false );
			}
		}

		function resetCapture() {
			setEditing( null );
			setOrder( null );
			setOrderId( '' );
			setSelectedItem( '' );
			setEventKind( 'confirmed_return' );
			setReasonId( '' );
			setQuantity( '1' );
			setOccurredDate( config.today );
		}

		async function saveEvent( event ) {
			event.preventDefault();
			if ( !config.canManage ) {
				return;
			}
			if ( !order || !selectedItem || !reasonId ) {
				setNotice( { status: 'error', message: config.strings.completeRequired } );
				return;
			}
			setSaving( true );
			setNotice( null );
			const data = {
				event_kind: eventKind,
				order_id: Number( order.order_id ),
				order_item_id: Number( selectedItem ),
				quantity: Number( quantity ),
				reason_id: reasonId,
				occurred_at: occurredDate
			};
			try {
				if ( editing ) {
					await apiFetch( { path: namespace + '/events/' + editing.id, method: 'PATCH', data: data } );
					setNotice( { status: 'success', message: config.strings.eventUpdated } );
				} else {
					data.idempotency_key = makeKey();
					await apiFetch( { path: namespace + '/events', method: 'POST', data: data } );
					setNotice( { status: 'success', message: config.strings.eventSaved } );
				}
				await loadEvents( 1, historyOrderId );
				await loadReport( reportFrom, reportTo );
				await loadSources();
				resetCapture();
			} catch ( error ) {
				showError( error, config.strings.saveError );
			} finally {
				setSaving( false );
			}
		}

		function editEvent( record ) {
			setTab( 'capture' );
			setEditing( record );
			setOrderId( String( record.order_id ) );
			loadOrder( record.order_id, record );
		}

		async function deleteEvent( record ) {
			if ( !deleteNote.trim() ) {
				return;
			}
			try {
				await apiFetch( {
					path: namespace + '/events/' + record.id,
					method: 'DELETE',
					data: { audit_reason: deleteNote.trim() }
				} );
				setNotice( { status: 'success', message: config.strings.eventDeleted } );
				setDeleteTarget( null );
				setDeleteNote( '' );
				await loadEvents( page, historyOrderId );
				await loadReport( reportFrom, reportTo );
				await loadSources();
			} catch ( error ) {
				showError( error, config.strings.deleteError );
			}
		}

		function beginReasonEdit( reason ) {
			setEditingReason( reason );
			setReasonLabel( reason.label );
			setReasonGroup( reason.group );
			setTranslations( Object.keys( reason.translations || {} ).map( function ( locale ) {
				return { locale: locale, label: reason.translations[ locale ] };
			} ) );
		}

		function clearReasonForm() {
			setEditingReason( null );
			setReasonLabel( '' );
			setReasonGroup( 'product' );
			setTranslations( [] );
		}

		async function saveReason( event ) {
			event.preventDefault();
			if ( !config.canManage || !reasonLabel.trim() ) {
				return;
			}
			setCatalogBusy( true );
			setNotice( null );
			const labels = {};
			translations.forEach( function ( row ) {
				if ( row.locale && row.label.trim() ) {
					labels[ row.locale.trim() ] = row.label.trim();
				}
			} );
			const body = { label: reasonLabel.trim(), group: reasonGroup, translations: labels };
			try {
				if ( editingReason ) {
					await apiFetch( { path: namespace + '/reasons/' + editingReason.id, method: 'PATCH', data: body } );
					setNotice( { status: 'success', message: config.strings.reasonUpdated } );
				} else {
					await apiFetch( { path: namespace + '/reasons', method: 'POST', data: body } );
					setNotice( { status: 'success', message: config.strings.reasonCreated } );
				}
				clearReasonForm();
				await loadReasons();
			} catch ( error ) {
				showError( error, config.strings.reasonSaveError );
			} finally {
				setCatalogBusy( false );
			}
		}

		async function toggleReason( reason ) {
			try {
				await apiFetch( { path: namespace + '/reasons/' + reason.id, method: 'PATCH', data: { active: !reason.active } } );
				await loadReasons();
				setNotice( { status: 'success', message: reason.active ? config.strings.reasonDisabled : config.strings.reasonEnabled } );
			} catch ( error ) {
				showError( error, config.strings.reasonSaveError );
			}
		}

		const activeReasons = reasons.map( function ( item ) {
			return { label: item.label, value: item.id };
		} );
		const selectedOrderItem = order && order.items ? order.items.find( function ( item ) {
			return String( item.order_item_id ) === String( selectedItem );
		} ) : null;

		function field( label, id, input, hint ) {
			return h( 'div', { className: 'wpe-ri-field' },
				h( 'label', { htmlFor: id }, label ),
				input,
				hint ? h( 'p', { className: 'wpe-ri-hint' }, hint ) : null
			);
		}

		function nativeSelect( id, value, options, onChange, disabled ) {
			return h( 'select', { id: id, value: value, onChange: function ( e ) { onChange( e.target.value ); }, disabled: !!disabled },
				options.map( function ( option ) {
					return h( 'option', { key: option.value, value: option.value }, option.label );
				} )
			);
		}


		function renderReportTable( title, headers, rows, keyName ) {
			return h( 'section', { className: 'wpe-ri-report-section' },
				h( 'h3', null, title ),
				rows.length ? h( 'div', { className: 'wpe-ri-table-wrap' },
					h( 'table', { className: 'wpe-ri-table' },
						h( 'thead', null, h( 'tr', null, headers.map( function ( label, index ) { return h( 'th', { key: 'head-' + index, scope: 'col' }, label ); } ) ) ),
						h( 'tbody', null, rows.map( function ( row ) {
							return h( 'tr', { key: row[ keyName ] }, row.cells.map( function ( value, index ) { return h( 'td', { key: row[ keyName ] + '-' + index }, value ); } ) );
						} ) )
					)
				) : h( 'p', { className: 'wpe-ri-muted' }, config.strings.noReportRows )
			);
		}

		function renderOverview() {
			const total = report && report.totals ? report.totals.event_count : 0;
			const quantityTotal = report && report.totals ? report.totals.quantity_total : 0;
			const coverage = report && report.mapping_coverage ? report.mapping_coverage : null;
			const coverageText = coverage && coverage.percent !== null ? coverage.percent + '%' : '—';
			const reasonRows = report && report.by_reason ? report.by_reason.map( function ( row ) {
				return { reason_id: row.reason_id, cells: [ row.label, row.sample_size, row.quantity_total ] };
			} ) : [];
			const kindRows = report && report.by_event_kind ? report.by_event_kind.map( function ( row ) {
				return { event_kind: row.event_kind, cells: [ config.strings.eventKinds[ row.event_kind ] || row.event_kind, row.event_count, row.quantity_total ] };
			} ) : [];
			const productRows = report && report.by_product ? report.by_product.map( function ( row ) {
				return { product_key: row.product_id + '-' + row.variation_id, cells: [ row.label, row.event_count, row.quantity_total ] };
			} ) : [];
			const categoryRows = report && report.by_category ? report.by_category.map( function ( row ) {
				return { term_id: row.term_id, cells: [ row.name, row.event_count, row.quantity_total ] };
			} ) : [];
			const sourceRowsForReport = report && report.by_source ? report.by_source.map( function ( row ) {
				return { source_type: row.source_type, cells: [ row.source_type === 'manual' ? config.strings.manualSourceTitle : config.strings.refundSourceTitle, row.event_count ] };
			} ) : [];
			return h( 'section', { className: 'wpe-ri-panel', 'aria-labelledby': 'wpe-ri-overview-heading' },
				h( 'div', { className: 'wpe-ri-panel-heading' },
					h( 'div', null,
						h( 'p', { className: 'wpe-ri-eyebrow' }, config.strings.overviewEyebrow ),
						h( 'h2', { id: 'wpe-ri-overview-heading' }, config.strings.overviewTitle ),
						h( 'p', { className: 'wpe-ri-muted' }, config.strings.overviewIntro )
					),
					h( Button, { variant: 'secondary', onClick: function () { loadReport( reportFrom, reportTo ); loadSources(); }, disabled: reportBusy }, config.strings.refresh )
				),
				h( 'form', { className: 'wpe-ri-report-filters', onSubmit: function ( event ) { event.preventDefault(); loadReport( reportFrom, reportTo ); } },
					field( config.strings.reportFrom, 'wpe-ri-report-from', h( 'input', { id: 'wpe-ri-report-from', type: 'date', value: reportFrom, max: reportTo, onChange: function ( event ) { setReportFrom( event.target.value ); } } ) ),
					field( config.strings.reportTo, 'wpe-ri-report-to', h( 'input', { id: 'wpe-ri-report-to', type: 'date', value: reportTo, min: reportFrom, max: config.today, onChange: function ( event ) { setReportTo( event.target.value ); } } ) ),
					h( Button, { variant: 'primary', type: 'submit', disabled: reportBusy || !reportFrom || !reportTo }, config.strings.runReport )
				),
				reportBusy ? h( 'div', { className: 'wpe-ri-loading', role: 'status' }, h( Spinner ), h( 'span', null, config.strings.loading ) ) : report ? h( 'div', null,
					h( 'p', { className: 'wpe-ri-report-period' }, config.strings.periodLabel + ': ' + report.period.date_from + ' – ' + report.period.date_to + ' (' + report.period.timezone + ')' ),
					h( 'div', { className: 'wpe-ri-stat-grid' },
						h( 'article', { className: 'wpe-ri-stat-card' }, h( 'span', { className: 'wpe-ri-stat-label' }, config.strings.totalInsights ), h( 'strong', null, total ), h( 'small', null, config.strings.eventCountHelp ) ),
						h( 'article', { className: 'wpe-ri-stat-card' }, h( 'span', { className: 'wpe-ri-stat-label' }, config.strings.totalQuantity ), h( 'strong', null, quantityTotal ), h( 'small', null, config.strings.quantityHelp ) ),
						h( 'article', { className: 'wpe-ri-stat-card' }, h( 'span', { className: 'wpe-ri-stat-label' }, config.strings.mappingCoverage ), h( 'strong', null, coverageText ), h( 'small', null, coverage ? coverage.mapped_events + ' / ' + coverage.total_events + ' ' + config.strings.mappedEvents : config.strings.noMappingData ) )
					),
					h( 'p', { className: 'wpe-ri-report-note' }, config.strings.reportBoundary ),
					total ? h( 'div', { className: 'wpe-ri-report-grid' },
						renderReportTable( config.strings.reasonBreakdown, [ config.strings.reason, config.strings.sampleSize, config.strings.quantity ], reasonRows, 'reason_id' ),
						renderReportTable( config.strings.eventKindBreakdown, [ config.strings.type, config.strings.eventCount, config.strings.quantity ], kindRows, 'event_kind' ),
						renderReportTable( config.strings.sourceBreakdown, [ config.strings.captureSource, config.strings.eventCount ], sourceRowsForReport, 'source_type' ),
						renderReportTable( config.strings.productBreakdown, [ config.strings.product, config.strings.eventCount, config.strings.quantity ], productRows, 'product_key' ),
						renderReportTable( config.strings.categoryBreakdown, [ config.strings.category, config.strings.eventCount, config.strings.quantity ], categoryRows, 'term_id' )
					) : h( 'div', { className: 'wpe-ri-empty' }, h( 'h3', null, config.strings.noReportData ), h( 'p', null, config.strings.noReportDataHelp ) )
				) : null,
				h( 'div', { className: 'wpe-ri-overview-actions' },
					h( Button, { variant: 'primary', onClick: function () { setTab( 'capture' ); } }, config.strings.insightStart ),
					h( Button, { variant: 'secondary', onClick: function () { setTab( 'history' ); loadEvents( 1, '' ); } }, config.strings.historyTab )
				)
			);
		}

		function renderPatterns() {
			const days = [ 30, 60, 90, 120, 180 ].map( function ( day ) {
				return { value: String( day ), label: config.strings.dayCount.replace( '%d', String( day ) ) };
			} );
			const rows = patternData && patternData.patterns ? patternData.patterns : [];
			function rateText( value, status ) {
				if ( null !== value && undefined !== value ) { return value + '%'; }
				if ( status === 'incomplete_coverage' ) { return config.strings.incompleteCoverage; }
				if ( status === 'insufficient_denominator' ) { return config.strings.insufficientDenominator; }
				if ( status === 'low_sample' ) { return config.strings.lowSampleRateSuppressed; }
				if ( status === 'return_quantity_exceeds_sold' ) { return config.strings.returnExceedsSold; }
				return config.strings.rateUnavailable;
			}
			function quantityPair( data ) {
				return data.returned_quantity + ' / ' + data.sold_quantity;
			}
			function qualityText( current, comparison ) {
				const flags = Array.from( new Set( ( current.quality_flags || [] ).concat( comparison.quality_flags || [] ) ) );
				return flags.map( function ( flag ) {
					return flag === 'low_sample' ? config.strings.lowSample : flag === 'incomplete_coverage' ? config.strings.incompleteCoverage : flag;
				} ).join( ', ' ) || '—';
			}
			const tableRows = rows.map( function ( row ) {
				return {
					key: row.product_id + '-' + row.variation_id + '-' + row.reason_id,
					cells: [
						row.variation_label ? row.product_label + ' — ' + row.variation_label : row.product_label,
						row.reason_label,
						quantityPair( row.current ),
						rateText( row.current.rate_percent, row.current.rate_status ),
						quantityPair( row.comparison ),
						rateText( row.comparison.rate_percent, row.comparison.rate_status ),
						null === row.change_percentage_points ? '—' : ( row.change_percentage_points > 0 ? '+' : '' ) + row.change_percentage_points,
						qualityText( row.current, row.comparison )
					]
				};
			} );
			return h( 'section', { className: 'wpe-ri-panel', 'aria-labelledby': 'wpe-ri-patterns-heading' },
				h( 'div', { className: 'wpe-ri-panel-heading' },
					h( 'div', null,
						h( 'p', { className: 'wpe-ri-eyebrow' }, config.strings.patternsTab ),
						h( 'h2', { id: 'wpe-ri-patterns-heading' }, config.strings.patternsTitle ),
						h( 'p', { className: 'wpe-ri-muted' }, config.strings.patternsIntro )
					)
				),
				!config.proEnabled ? h( 'div', { className: 'wpe-ri-pro-lock', role: 'note' },
					h( 'span', { className: 'wpe-ri-pro-lock-badge' }, 'PRO' ),
					h( 'h3', null, config.strings.proLockedTitle ),
					h( 'p', null, config.strings.proLockedIntro )
				) : h( 'div', null,
					h( 'form', { className: 'wpe-ri-report-filters', onSubmit: function ( event ) {
						event.preventDefault();
						loadPatterns( patternMaturity, cohortDays );
					} },
						field( config.strings.maturityDays, 'wpe-ri-pattern-maturity', nativeSelect( 'wpe-ri-pattern-maturity', patternMaturity, days, setPatternMaturity, patternBusy ) ),
						field( config.strings.cohortDays, 'wpe-ri-pattern-cohort', nativeSelect( 'wpe-ri-pattern-cohort', cohortDays, days, setCohortDays, patternBusy ) ),
						h( Button, { variant: 'primary', type: 'submit', disabled: patternBusy }, config.strings.updatePatterns )
					),
					patternBusy ? h( 'div', { className: 'wpe-ri-loading', role: 'status' }, h( Spinner ), h( 'span', null, config.strings.loading ) ) : patternData ? h( 'div', null,
						h( 'p', { className: 'wpe-ri-report-period' }, config.strings.currentCohort + ': ' + patternData.periods.current.date_from + ' – ' + patternData.periods.current.date_to + ' · ' + config.strings.comparisonCohort + ': ' + patternData.periods.comparison.date_from + ' – ' + patternData.periods.comparison.date_to + ' (' + patternData.periods.timezone + ')' ),
						h( 'p', { className: 'wpe-ri-report-note' }, config.strings.patternBoundary ),
						!patternData.coverage.current.complete || !patternData.coverage.comparison.complete ? h( Notice, { status: 'warning', isDismissible: false },
							patternData.coverage.current.unavailable_reason === 'order_scan_limit' || patternData.coverage.comparison.unavailable_reason === 'order_scan_limit' ? config.strings.orderScanLimit : config.strings.incompleteCoverage
						) : null,
						tableRows.length ? h( 'div', { className: 'wpe-ri-table-wrap' },
							h( 'table', { className: 'wpe-ri-table wpe-ri-pattern-table' },
								h( 'thead', null, h( 'tr', null, [
									config.strings.product, config.strings.reason, config.strings.eligibleSold, config.strings.currentRate,
									config.strings.eligibleSold, config.strings.comparisonRate, config.strings.rateChange, config.strings.status
								].map( function ( label, index ) { return h( 'th', { key: 'pattern-head-' + index, scope: 'col' }, label ); } ) ) ),
								h( 'tbody', null, tableRows.map( function ( row ) {
									return h( 'tr', { key: row.key }, row.cells.map( function ( value, index ) { return h( 'td', { key: row.key + '-' + index }, value ); } ) );
								} ) )
							)
						) : h( 'div', { className: 'wpe-ri-empty' }, h( 'h3', null, !patternData.coverage.current.complete || !patternData.coverage.comparison.complete ? config.strings.noPatternCoverage : config.strings.noPatterns ), h( 'p', null, !patternData.coverage.current.complete || !patternData.coverage.comparison.complete ? config.strings.incompleteCoverage : config.strings.noPatternsHelp ) )
					) : null
				)
			);
		}

		function renderSources() {
			const descriptions = {
				manual: config.strings.manualSource,
				woocommerce_refund: config.strings.refundSource
			};
			return h( 'section', { className: 'wpe-ri-panel', 'aria-labelledby': 'wpe-ri-sources-heading' },
				h( 'div', { className: 'wpe-ri-panel-heading' },
					h( 'div', null,
						h( 'p', { className: 'wpe-ri-eyebrow' }, config.strings.sourcesEyebrow ),
						h( 'h2', { id: 'wpe-ri-sources-heading' }, config.strings.sourcesTitle ),
						h( 'p', { className: 'wpe-ri-muted' }, config.strings.sourcesIntro )
					),
					h( Button, { variant: 'secondary', onClick: loadSources }, config.strings.refresh )
				),
				sourceBusy ? h( 'div', { className: 'wpe-ri-loading' }, h( Spinner ), h( 'span', null, config.strings.loading ) ) :
					sourceRows.length ? h( 'div', { className: 'wpe-ri-source-list' }, sourceRows.map( function ( row ) {
						return h( 'article', { className: 'wpe-ri-source-card', key: row.source_type },
							h( 'div', { className: 'wpe-ri-source-icon', 'aria-hidden': true }, row.source_type === 'manual' ? '↳' : '↻' ),
							h( 'div', { className: 'wpe-ri-source-copy' },
								h( 'h3', null, row.source_type === 'manual' ? config.strings.manualSourceTitle : config.strings.refundSourceTitle ),
								h( 'p', null, descriptions[ row.source_type ] || config.strings.otherSource ),
								h( 'small', null, config.strings.lastSeen + ': ' + ( row.latest_event_date || '—' ) )
							),
							h( 'div', { className: 'wpe-ri-source-count' }, h( 'strong', null, row.event_count ), h( 'span', null, config.strings.recordCount )
							)
						);
					} ) ) : h( 'div', { className: 'wpe-ri-empty' },
						h( 'span', { className: 'wpe-ri-empty-icon', 'aria-hidden': true }, '↻' ),
						h( 'h3', null, config.strings.noSourceEvents ),
						h( 'p', null, config.strings.noSourceEventsHelp )
					),
				h( 'p', { className: 'wpe-ri-source-note' }, config.strings.sourcePrivacy )
			);
		}

		function renderCapture() {
			return h( 'section', { className: 'wpe-ri-panel', 'aria-labelledby': 'wpe-ri-capture-heading' },
				h( 'div', { className: 'wpe-ri-panel-heading' },
					h( 'div', null,
						h( 'p', { className: 'wpe-ri-eyebrow' }, config.strings.captureEyebrow ),
						h( 'h2', { id: 'wpe-ri-capture-heading' }, editing ? config.strings.editCapture : config.strings.newCapture ),
						h( 'p', { className: 'wpe-ri-muted' }, config.strings.captureIntro )
					),
					editing ? h( Button, { variant: 'secondary', onClick: resetCapture, disabled: saving }, config.strings.cancelEdit ) : null
				),
				!config.canManage ? h( Notice, { status: 'warning', isDismissible: false }, config.strings.readOnlyNotice ) : null,
				h( 'div', { className: 'wpe-ri-order-lookup' },
					field( config.strings.orderNumber, 'wpe-ri-order-id', h( 'input', {
						id: 'wpe-ri-order-id', type: 'number', min: '1', step: '1', value: orderId,
						onChange: function ( e ) { setOrderId( e.target.value ); },
						disabled: !!editing || !config.canManage
					} ), config.strings.orderLookupHint ),
					h( Button, {
						variant: 'secondary',
						onClick: function () { loadOrder( orderId ); },
						disabled: orderBusy || !config.canManage
					}, orderBusy ? h( Spinner ) : config.strings.loadOrder )
				),
				order ? h( 'div', { className: 'wpe-ri-order-summary', role: 'status' },
					h( 'span', { className: 'wpe-ri-order-mark' }, '#' + order.order_number ),
					h( 'span', null, ( order.items || [] ).length + ' ' + config.strings.lineItems )
				) : null,
				h( 'form', { onSubmit: saveEvent, className: 'wpe-ri-capture-form' },
					h( 'div', { className: 'wpe-ri-form-grid' },
						field( config.strings.productItem, 'wpe-ri-item', nativeSelect( 'wpe-ri-item', selectedItem, ( order && order.items ? order.items : [] ).map( function ( item ) {
							return { value: String( item.order_item_id ), label: item.name + ' · ' + config.strings.ordered + ': ' + item.ordered_quantity };
						} ), setSelectedItem, !order || !config.canManage ) ),
						field( config.strings.reason, 'wpe-ri-reason', nativeSelect( 'wpe-ri-reason', reasonId, [ { value: '', label: config.strings.chooseReason } ].concat( activeReasons ), setReasonId, !config.canManage ) ),
						field( config.strings.eventType, 'wpe-ri-event-kind', nativeSelect( 'wpe-ri-event-kind', eventKind, [
							{ value: 'confirmed_return', label: config.strings.confirmedReturn },
							{ value: 'exchange', label: config.strings.exchange }
						], setEventKind, !config.canManage ) ),
						field( config.strings.quantity, 'wpe-ri-quantity', h( 'input', {
							id: 'wpe-ri-quantity', type: 'number', min: '0.0001', step: '0.0001',
							max: selectedOrderItem ? selectedOrderItem.ordered_quantity : undefined,
							value: quantity, onChange: function ( e ) { setQuantity( e.target.value ); }, disabled: !config.canManage
						} ), selectedOrderItem ? config.strings.quantityHint.replace( '%s', String( selectedOrderItem.ordered_quantity ) ) : '' ),
						field( config.strings.occurredDate, 'wpe-ri-date', h( 'input', {
							id: 'wpe-ri-date', type: 'date', value: occurredDate,
							onChange: function ( e ) { setOccurredDate( e.target.value ); }, disabled: !config.canManage
						} ), config.strings.siteDateHint )
					),
					h( 'div', { className: 'wpe-ri-form-actions' },
						h( Button, { variant: 'primary', type: 'submit', disabled: saving || !config.canManage || !order || !selectedItem }, saving ? h( Spinner ) : ( editing ? config.strings.updateInsight : config.strings.saveInsight ) ),
						editing ? h( 'span', { className: 'wpe-ri-muted' }, config.strings.editScope ) : null
					)
				)
			);
		}

		function renderHistory() {
			const rows = events.map( function ( item ) {
				return h( 'tr', { key: item.id },
					h( 'td', null, item.occurred_date || item.occurred_at ),
					h( 'td', null, '#' + item.order_id ),
					h( 'td', null, item.attribution_scope === 'refund' ? config.strings.refundScope : ( item.order_item_id ? '#' + item.order_item_id : '—' ) ),
					h( 'td', null, item.reason_label || config.strings.unknown ),
					h( 'td', null, item.event_kind === 'confirmed_return' ? config.strings.confirmedReturn : item.event_kind === 'exchange' ? config.strings.exchange : config.strings.refundOnly ),
					h( 'td', null, item.quantity ),
					h( 'td', { className: 'wpe-ri-actions-cell' },
						item.source_type === 'manual' && config.canManage ? h( Button, { variant: 'secondary', onClick: function () { editEvent( item ); } }, config.strings.edit ) : null,
						item.source_type === 'manual' && config.canManage ? h( Button, { variant: 'tertiary', onClick: function () { setDeleteTarget( item ); setDeleteNote( '' ); } }, config.strings.delete ) : null,
						deleteTarget && deleteTarget.id === item.id ? h( 'div', { className: 'wpe-ri-delete-confirm' },
							h( 'label', { htmlFor: 'wpe-ri-delete-note' }, config.strings.deleteReason ),
							h( 'input', { id: 'wpe-ri-delete-note', value: deleteNote, maxLength: 200, onChange: function ( e ) { setDeleteNote( e.target.value ); } } ),
							h( Button, { variant: 'primary', isDestructive: true, disabled: !deleteNote.trim(), onClick: function () { deleteEvent( item ); } }, config.strings.confirmDelete ),
							h( Button, { variant: 'tertiary', onClick: function () { setDeleteTarget( null ); } }, config.strings.cancelDelete )
						) : null
					)
				);
			} );
			return h( 'section', { className: 'wpe-ri-panel', 'aria-labelledby': 'wpe-ri-history-heading' },
				h( 'div', { className: 'wpe-ri-panel-heading' },
					h( 'div', null,
						h( 'p', { className: 'wpe-ri-eyebrow' }, config.strings.historyEyebrow ),
						h( 'h2', { id: 'wpe-ri-history-heading' }, config.strings.eventHistory ),
						h( 'p', { className: 'wpe-ri-muted' }, config.strings.historyIntro )
					),
					h( Button, { variant: 'secondary', onClick: function () { loadEvents( 1, historyOrderId ); } }, config.strings.refresh )
				),
				h( 'div', { className: 'wpe-ri-history-filter' },
					field( config.strings.filterOrder, 'wpe-ri-filter-order', h( 'input', {
						id: 'wpe-ri-filter-order', type: 'number', min: '1', value: historyOrderId,
						onChange: function ( e ) { setHistoryOrderId( e.target.value ); },
						onKeyDown: function ( e ) { if ( e.key === 'Enter' ) { loadEvents( 1, historyOrderId ); } }
					} ), config.strings.filterHint ),
					h( Button, { variant: 'secondary', onClick: function () { loadEvents( 1, historyOrderId ); } }, config.strings.applyFilter ),
					h( Button, { variant: 'tertiary', onClick: function () { setHistoryOrderId( '' ); loadEvents( 1, '' ); } }, config.strings.clearFilter )
				),
				loadingEvents ? h( 'div', { className: 'wpe-ri-loading' }, h( Spinner ), h( 'span', null, config.strings.loading ) ) :
					events.length ? h( 'div', { className: 'wpe-ri-table-wrap' },
						h( 'table', { className: 'wpe-ri-table' },
							h( 'thead', null, h( 'tr', null,
								[ config.strings.date, config.strings.order, config.strings.item, config.strings.reason, config.strings.type, config.strings.quantity, config.strings.actions ].map( function ( title ) { return h( 'th', { key: title, scope: 'col' }, title ); } )
							) ),
							h( 'tbody', null, rows )
						),
						h( 'div', { className: 'wpe-ri-pagination' },
							h( 'span', { className: 'wpe-ri-muted' }, config.strings.page.replace( '%1$s', String( page ) ).replace( '%2$s', String( totalPages ) ) ),
							h( Button, { variant: 'secondary', disabled: page <= 1 || loadingEvents, onClick: function () { loadEvents( page - 1, historyOrderId ); } }, config.strings.previous ),
							h( Button, { variant: 'secondary', disabled: page >= totalPages || loadingEvents, onClick: function () { loadEvents( page + 1, historyOrderId ); } }, config.strings.next )
						)
					) : h( 'div', { className: 'wpe-ri-empty' },
						h( 'span', { className: 'wpe-ri-empty-icon', 'aria-hidden': true }, '↗' ),
						h( 'h3', null, config.strings.noEvents ),
						h( 'p', null, config.strings.noEventsHelp )
					)
			);
		}

		function renderCatalog() {
			const rows = catalog.map( function ( reason ) {
				return h( 'tr', { key: reason.id, className: reason.active ? '' : 'is-inactive' },
					h( 'td', null, h( 'strong', null, reason.label ), h( 'code', { className: 'wpe-ri-key' }, reason.id ) ),
					h( 'td', null, config.strings.groups[ reason.group ] || reason.group ),
					h( 'td', null, ( reason.translations && Object.keys( reason.translations ).length ) ? Object.keys( reason.translations ).map( function ( locale ) {
						return h( 'span', { className: 'wpe-ri-locale', key: locale }, locale + ': ' + reason.translations[ locale ] );
					} ) : h( 'span', { className: 'wpe-ri-muted' }, config.strings.noTranslations ) ),
					h( 'td', null, h( 'span', { className: 'wpe-ri-status ' + ( reason.active ? 'is-active' : 'is-inactive' ) }, reason.active ? config.strings.active : config.strings.inactive ) ),
					h( 'td', { className: 'wpe-ri-actions-cell' },
						config.canManage ? h( Button, { variant: 'secondary', onClick: function () { beginReasonEdit( reason ); } }, config.strings.edit ) : null,
						config.canManage ? h( Button, { variant: 'tertiary', onClick: function () { toggleReason( reason ); } }, reason.active ? config.strings.deactivate : config.strings.activate ) : null
					)
				);
			} );
			return h( 'section', { className: 'wpe-ri-panel', 'aria-labelledby': 'wpe-ri-catalog-heading' },
				h( 'div', { className: 'wpe-ri-panel-heading' },
					h( 'div', null,
						h( 'p', { className: 'wpe-ri-eyebrow' }, config.strings.catalogEyebrow ),
						h( 'h2', { id: 'wpe-ri-catalog-heading' }, config.strings.reasonCatalog ),
						h( 'p', { className: 'wpe-ri-muted' }, config.strings.catalogIntro )
					)
				),
				config.canManage ? h( 'form', { className: 'wpe-ri-reason-form', onSubmit: saveReason },
					h( 'h3', null, editingReason ? config.strings.editReason : config.strings.addReason ),
					h( 'div', { className: 'wpe-ri-form-grid' },
						field( config.strings.reasonLabel, 'wpe-ri-reason-label', h( 'input', { id: 'wpe-ri-reason-label', maxLength: 100, required: true, value: reasonLabel, onChange: function ( e ) { setReasonLabel( e.target.value ); } } ) ),
						field( config.strings.reasonGroup, 'wpe-ri-reason-group', nativeSelect( 'wpe-ri-reason-group', reasonGroup, [
							{ value: 'product', label: config.strings.groups.product },
							{ value: 'fulfillment', label: config.strings.groups.fulfillment },
							{ value: 'other', label: config.strings.groups.other }
						], setReasonGroup ) )
					),
					h( 'div', { className: 'wpe-ri-translations' },
						h( 'div', { className: 'wpe-ri-translation-heading' },
							h( 'div', null, h( 'h4', null, config.strings.translatedLabels ), h( 'p', { className: 'wpe-ri-hint' }, config.strings.translationsHint ) ),
							h( Button, { variant: 'secondary', type: 'button', onClick: function () { setTranslations( translations.concat( [ { locale: '', label: '' } ] ) ); } }, config.strings.addLanguage )
						),
						translations.map( function ( row, index ) {
							return h( 'div', { className: 'wpe-ri-translation-row', key: 'translation-' + index },
								field( config.strings.locale, 'wpe-ri-locale-' + index, h( 'input', { id: 'wpe-ri-locale-' + index, placeholder: 'fr_FR', value: row.locale, onChange: function ( e ) {
									const next = translations.slice(); next[ index ] = { locale: e.target.value, label: row.label }; setTranslations( next );
								} } ) ),
								field( config.strings.translatedLabel, 'wpe-ri-translated-label-' + index, h( 'input', { id: 'wpe-ri-translated-label-' + index, maxLength: 100, value: row.label, onChange: function ( e ) {
									const next = translations.slice(); next[ index ] = { locale: row.locale, label: e.target.value }; setTranslations( next );
								} } ) ),
								h( Button, { variant: 'tertiary', type: 'button', onClick: function () { setTranslations( translations.filter( function ( _, i ) { return i !== index; } ) ); } }, config.strings.removeLanguage )
							);
						} )
					),
					h( 'div', { className: 'wpe-ri-form-actions' },
						h( Button, { variant: 'primary', type: 'submit', disabled: catalogBusy || !reasonLabel.trim() }, catalogBusy ? h( Spinner ) : ( editingReason ? config.strings.updateReason : config.strings.createReason ) ),
						editingReason ? h( Button, { variant: 'tertiary', type: 'button', onClick: clearReasonForm }, config.strings.cancelEdit ) : null
					)
				) : h( Notice, { status: 'info', isDismissible: false }, config.strings.readOnlyNotice ),
				catalogBusy ? h( 'div', { className: 'wpe-ri-loading' }, h( Spinner ), h( 'span', null, config.strings.loading ) ) :
					h( 'div', { className: 'wpe-ri-table-wrap' },
						h( 'table', { className: 'wpe-ri-table wpe-ri-catalog-table' },
							h( 'thead', null, h( 'tr', null, [ config.strings.reason, config.strings.group, config.strings.translatedLabels, config.strings.status, config.strings.actions ].map( function ( title ) { return h( 'th', { key: title, scope: 'col' }, title ); } ) ) ),
							h( 'tbody', null, rows )
						)
					)
			);
		}

		const tabs = [
			{ id: 'overview', label: config.strings.overviewTab },
			{ id: 'history', label: config.strings.historyTab },
			{ id: 'capture', label: config.strings.captureTab },
			{ id: 'catalog', label: config.strings.catalogTab },
			{ id: 'sources', label: config.strings.sourcesTab },
			{ id: 'patterns', label: config.strings.patternsTab },
			{ id: 'alerts', label: config.strings.alertsTab }
		];

		return h( 'div', { className: 'wpe-ri-app' },
			h( 'header', { className: 'wpe-ri-hero' },
				h( 'div', { className: 'wpe-ri-hero-mark', 'aria-hidden': true }, 'RI' ),
				h( 'div', null,
					h( 'p', { className: 'wpe-ri-eyebrow' }, config.strings.brand ),
					h( 'h1', null, config.strings.title ),
					h( 'p', { className: 'wpe-ri-hero-copy' }, config.strings.subtitle )
				)
			),
			notice ? h( Notice, { status: notice.status, isDismissible: true, onRemove: function () { setNotice( null ); } }, notice.message ) : null,
			h( 'nav', { className: 'wpe-ri-tabs', 'aria-label': config.strings.navigation },
				tabs.map( function ( item ) {
					return h( 'button', {
						key: item.id, type: 'button', className: 'wpe-ri-tab' + ( tab === item.id ? ' is-current' : '' ),
						'aria-current': tab === item.id ? 'page' : undefined,
						onClick: function () {
							setTab( item.id );
							if ( item.id === 'history' ) { loadEvents( 1, historyOrderId ); }
							if ( item.id === 'catalog' ) { loadReasons(); }
							if ( item.id === 'overview' ) { loadReport( reportFrom, reportTo ); loadSources(); }
							if ( item.id === 'sources' ) { loadSources(); }
							if ( item.id === 'patterns' && config.proEnabled ) { loadPatterns( patternMaturity, cohortDays ); }
							if ( item.id === 'alerts' ) { loadAlerts(); }
						}
					}, item.label );
				} )
			),
			tab === 'overview' ? renderOverview() : tab === 'capture' ? renderCapture() : tab === 'history' ? renderHistory() : tab === 'sources' ? renderSources() : tab === 'patterns' ? h( wp.element.Fragment, null, renderPatterns(), renderClusters() ) : tab === 'alerts' ? renderAlerts() : renderCatalog(),
			h( 'footer', { className: 'wpe-ri-footer' }, config.strings.footer )
		);
	}

	const root = document.getElementById( config.rootId );
	if ( root ) {
		wp.element.createRoot( root ).render( h( App ) );
	}
} )( window.wp, window.WPEssentialReturnInsights || {} );
