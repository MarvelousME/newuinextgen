/* Beyond Measure fallback SPA (no webpack). Uses wp.element + wp.apiFetch. */
( function ( wp ) {
	'use strict';
	if ( ! wp || ! wp.element || ! window.ngtbmBoot ) return;
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var apiFetch = wp.apiFetch;
	var boot = window.ngtbmBoot;
	apiFetch.use( apiFetch.createNonceMiddleware( boot.nonce ) );
	apiFetch.use( apiFetch.createRootURLMiddleware( boot.restRoot.replace( /\/$/, '' ) + '/' ) );

	function get( path ) {
		return apiFetch( { path: path } ).then( function ( res ) {
			return res && res.data !== undefined ? res.data : res;
		} );
	}

	function Placeholder( props ) {
		return el( 'div', { className: 'ngtbm-placeholder' }, el( 'h1', { style: { marginTop: 0, color: 'var(--ngtbm-text)' } }, props.path ), el( 'p', null, 'Structured placeholder — register a subsystem to populate this section.' ) );
	}

	function CommandCenter() {
		var state = useState( null );
		var health = state[ 0 ];
		var setHealth = state[ 1 ];
		useEffect( function () {
			get( 'health' ).then( setHealth ).catch( function () {
				setHealth( { level: 'degraded', score: 0, subsystems: { total: 0, healthy: 0, degraded: 0, offline: 0 }, queue: { pending: 0, dlq: 0 }, security: { warnings: 0 }, last24h: { evaluations: 0, bookings: 0, errors: 0, dlq: 0 }, attention: [], activity: [] } );
			} );
		}, [] );
		if ( ! health ) return el( 'div', { className: 'ngtbm-placeholder' }, 'Loading Command Center…' );
		return el(
			'div',
			null,
			el( 'h1', { style: { marginTop: 0 } }, 'Command Center' ),
			el( 'div', { className: 'ngtbm-status' }, el( 'span', { className: 'ngtbm-dot' } ), health.level ),
			el( 'div', { className: 'ngtbm-grid cols-4', style: { marginTop: 12 } },
				el( 'div', { className: 'ngtbm-tile' }, el( 'h3', null, 'Subsystems' ), el( 'div', { className: 'ngtbm-metric' }, health.subsystems.healthy + '/' + health.subsystems.total ) ),
				el( 'div', { className: 'ngtbm-tile' }, el( 'h3', null, 'Score' ), el( 'div', { className: 'ngtbm-metric' }, health.score + '%' ) ),
				el( 'div', { className: 'ngtbm-tile' }, el( 'h3', null, 'Queue' ), el( 'div', { className: 'ngtbm-metric' }, String( health.queue.pending ) ) ),
				el( 'div', { className: 'ngtbm-tile' }, el( 'h3', null, 'DLQ' ), el( 'div', { className: 'ngtbm-metric' }, String( health.queue.dlq ) ) )
			),
			el( 'div', { className: 'ngtbm-tile', style: { marginTop: 12 } }, el( 'h3', null, 'Attention' ), el( 'ul', null, ( health.attention || [] ).map( function ( a, i ) { return el( 'li', { key: i }, a.title ); } ) ) )
		);
	}

	function Talent() {
		var rowsState = useState( [] );
		var rows = rowsState[ 0 ];
		var setRows = rowsState[ 1 ];
		var explainState = useState( null );
		var explain = explainState[ 0 ];
		var setExplain = explainState[ 1 ];
		useEffect( function () {
			get( 'resources/talent-evaluation' ).then( function ( d ) { setRows( d.items || [] ); } ).catch( function () { setRows( [] ); } );
		}, [] );
		return el(
			'div',
			null,
			el( 'h1', { style: { marginTop: 0 } }, 'Talent Intelligence' ),
			el( 'div', { className: 'ngtbm-tile' },
				el( 'table', { className: 'ngtbm-table' },
					el( 'thead', null, el( 'tr', null, el( 'th', null, 'Tutor' ), el( 'th', null, 'Match' ), el( 'th', null, 'Status' ), el( 'th', null, 'Action' ) ) ),
					el( 'tbody', null, rows.map( function ( r ) {
						return el( 'tr', { key: r.id },
							el( 'td', null, r.tutor ),
							el( 'td', null, r.score + '%' ),
							el( 'td', null, r.status || r.recommendation ),
							el( 'td', null, el( 'button', { className: 'ngtbm-btn', type: 'button', onClick: function () { get( 'talent/evaluations/' + r.id + '/explain' ).then( setExplain ); } }, 'Explain →' ) )
						);
					} ) )
				)
			),
			explain ? el( 'aside', { className: 'ngtbm-drawer' },
				el( 'h2', null, 'Tutor Suitability — ' + explain.tutorId ),
				el( 'div', { className: 'ngtbm-metric' }, explain.overall + '%' ),
				el( 'button', { type: 'button', className: 'ngtbm-btn', onClick: function () { setExplain( null ); } }, 'Close' ),
				el( 'p', { style: { fontWeight: 700 } }, 'AI recommendation: ' + explain.recommendation )
			) : null
		);
	}

	function Matrix() {
		var state = useState( null );
		var data = state[ 0 ];
		var setData = state[ 1 ];
		useEffect( function () { get( 'access-matrix' ).then( setData ); }, [] );
		if ( ! data ) return el( 'div', { className: 'ngtbm-placeholder' }, 'Loading access matrix…' );
		return el( 'div', null, el( 'h1', { style: { marginTop: 0 } }, 'Access Matrix' ), el( 'p', null, data.roles.length + ' roles · ' + data.capabilities.length + ' capabilities' ) );
	}

	function Graph() {
		var state = useState( null );
		var data = state[ 0 ];
		var setData = state[ 1 ];
		useEffect( function () { get( 'dependency-graph' ).then( setData ); }, [] );
		if ( ! data ) return el( 'div', { className: 'ngtbm-placeholder' }, 'Loading dependency graph…' );
		return el( 'div', null,
			el( 'h1', { style: { marginTop: 0 } }, 'Dependency Map' ),
			el( 'div', { className: 'ngtbm-tile' }, el( 'h3', null, 'Nodes' ), el( 'ul', null, ( data.nodes || [] ).map( function ( n ) { return el( 'li', { key: n.id }, n.label + ' — ' + n.status ); } ) ) ),
			el( 'div', { className: 'ngtbm-tile', style: { marginTop: 12 } }, el( 'h3', null, 'Edges' ), el( 'ul', null, ( data.edges || [] ).slice( 0, 40 ).map( function ( e ) { return el( 'li', { key: e.id }, e.source + ' → ' + e.target + ( e.capability ? ' (' + e.capability + ')' : '' ) ); } ) ) )
		);
	}

	function App() {
		var pathState = useState( window.location.hash.replace( /^#/, '' ) || '/' );
		var path = pathState[ 0 ];
		var setPath = pathState[ 1 ];
		var navState = useState( [] );
		var nav = navState[ 0 ];
		var setNav = navState[ 1 ];
		useEffect( function () {
			function onHash() { setPath( window.location.hash.replace( /^#/, '' ) || '/' ); }
			window.addEventListener( 'hashchange', onHash );
			get( 'nav' ).then( setNav ).catch( function () { setNav( [] ); } );
			return function () { window.removeEventListener( 'hashchange', onHash ); };
		}, [] );
		function go( p ) { window.location.hash = p; setPath( p ); }
		var page = path.indexOf( '/tutors/talent' ) === 0 ? el( Talent ) : path.indexOf( '/security/' ) === 0 ? el( Matrix ) : ( path.indexOf( 'dependency-map' ) >= 0 || path.indexOf( 'architecture' ) >= 0 ) ? el( Graph ) : path === '/' ? el( CommandCenter ) : el( Placeholder, { path: path } );
		return el(
			'div',
			{ className: 'ngtbm-app' },
			el( 'aside', { className: 'ngtbm-sidebar' },
				el( 'div', { className: 'ngtbm-brand' }, 'Next Gen Tutors', el( 'span', null, 'Beyond Measure v' + boot.version ) ),
				nav.map( function ( item ) {
					return el( 'div', { key: item.id, className: 'ngtbm-nav-group' },
						el( 'div', { className: 'ngtbm-nav-label' }, item.label ),
						item.path ? el( 'button', { type: 'button', className: 'ngtbm-nav-item' + ( path === item.path ? ' is-active' : '' ), onClick: function () { go( item.path ); } }, item.label ) : null,
						( item.children || [] ).map( function ( c ) {
							return el( 'button', { key: c.id, type: 'button', className: 'ngtbm-nav-item' + ( path === c.path ? ' is-active' : '' ), onClick: function () { go( c.path ); } }, c.label );
						} )
					);
				} )
			),
			el( 'div', { className: 'ngtbm-main' },
				el( 'header', { className: 'ngtbm-topbar' }, el( 'div', null, 'Control Plane' ), el( 'div', { className: 'ngtbm-status' }, el( 'span', { className: 'ngtbm-dot' } ), 'SPA fallback' ) ),
				el( 'main', { className: 'ngtbm-content' }, page )
			)
		);
	}

	var root = document.getElementById( 'ngtbm-root' );
	if ( root && wp.element.createRoot ) {
		wp.element.createRoot( root ).render( el( App ) );
	} else if ( root && wp.element.render ) {
		wp.element.render( el( App ), root );
	}
} )( window.wp );
