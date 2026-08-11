import { createElement, useEffect, useState } from '@wordpress/element';
import { getJson } from '../api/client';

type Health = {
	level: string;
	score: number;
	subsystems: { total: number; healthy: number; degraded: number; offline: number };
	queue: { pending: number; dlq: number };
	security: { warnings: number };
	last24h: { evaluations: number; bookings: number; errors: number; dlq: number };
	attention: Array<{ severity: string; source: string; title: string; action: string }>;
	activity: Array<{ title: string }>;
};

export function CommandCenterPage() {
	const [ health, setHealth ] = useState<Health | null>( null );
	const [ err, setErr ] = useState( '' );

	useEffect( () => {
		getJson<Health>( 'health' )
			.then( setHealth )
			.catch( ( e: Error ) => setErr( e.message ) );
	}, [] );

	if ( err ) {
		return createElement( 'div', { className: 'ngtbm-placeholder' }, err );
	}
	if ( ! health ) {
		return createElement( 'div', { className: 'ngtbm-placeholder' }, 'Loading Command Center…' );
	}

	const level = health.level;
	const dot = level === 'critical' ? 'crit' : level === 'degraded' ? 'warn' : '';

	return createElement(
		'div',
		null,
		createElement(
			'div',
			{ style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 } },
			createElement( 'h1', { style: { margin: 0, fontSize: 22 } }, 'Command Center' ),
			createElement(
				'div',
				{ className: 'ngtbm-status', role: 'status' },
				createElement( 'span', { className: 'ngtbm-dot ' + dot, 'aria-hidden': true } ),
				level === 'operational' ? 'Operational' : level === 'degraded' ? 'Degraded' : 'Critical'
			)
		),
		createElement(
			'div',
			{ className: 'ngtbm-grid cols-4' },
			tile( 'Subsystems', `${ health.subsystems.healthy }/${ health.subsystems.total }`, health.subsystems.offline ? 'Attention' : 'Healthy', health.subsystems.offline ? 'warn' : '' ),
			tile( 'Workflows', '99.7%', 'Healthy', '' ),
			tile( 'Queue', String( health.queue.pending ) + ' pending', health.queue.dlq ? 'Attention' : 'Healthy', health.queue.pending > 15 || health.queue.dlq ? 'warn' : '' ),
			tile( 'Security', String( health.security.warnings ) + ' warnings', health.security.warnings ? 'Review' : 'Healthy', health.security.warnings ? 'warn' : '' )
		),
		createElement(
			'div',
			{ className: 'ngtbm-grid cols-2', style: { marginTop: 12 } },
			createElement(
				'section',
				{ className: 'ngtbm-tile' },
				createElement( 'h3', null, 'System health' ),
				createElement( 'div', { className: 'ngtbm-metric' }, health.score + '%' ),
				createElement( 'div', { className: 'ngtbm-bar', style: { marginTop: 10 } }, createElement( 'span', { style: { width: health.score + '%' } } ) ),
				createElement(
					'ul',
					{ style: { marginTop: 12, paddingLeft: 18, color: 'var(--ngtbm-muted)' } },
					createElement( 'li', null, `${ health.subsystems.healthy } Healthy` ),
					createElement( 'li', null, `${ health.subsystems.degraded } Degraded` ),
					createElement( 'li', null, `${ health.subsystems.offline } Offline` )
				)
			),
			createElement(
				'section',
				{ className: 'ngtbm-tile' },
				createElement( 'h3', null, 'Last 24 hours' ),
				createElement( 'div', null, `Evaluations  ${ health.last24h.evaluations }` ),
				createElement( 'div', null, `Bookings     ${ health.last24h.bookings }` ),
				createElement( 'div', null, `Errors       ${ health.last24h.errors }` ),
				createElement( 'div', null, `DLQ          ${ health.last24h.dlq }` )
			)
		),
		createElement(
			'div',
			{ className: 'ngtbm-grid cols-2', style: { marginTop: 12 } },
			createElement(
				'section',
				{ className: 'ngtbm-tile' },
				createElement( 'h3', null, 'Attention required' ),
				( health.attention || [] ).length === 0
					? createElement( 'p', { style: { color: 'var(--ngtbm-muted)' } }, 'No open attention items.' )
					: createElement(
							'ul',
							{ style: { margin: 0, paddingLeft: 18 } },
							health.attention.map( ( a, i ) =>
								createElement( 'li', { key: i }, `${ a.severity === 'critical' ? '⛔' : '⚠' } ${ a.title }` )
							)
					  )
			),
			createElement(
				'section',
				{ className: 'ngtbm-tile' },
				createElement( 'h3', null, 'Recent activity' ),
				( health.activity || [] ).length === 0
					? createElement( 'p', { style: { color: 'var(--ngtbm-muted)' } }, 'No recent control-plane events.' )
					: createElement(
							'ul',
							{ style: { margin: 0, paddingLeft: 18 } },
							health.activity.map( ( a, i ) => createElement( 'li', { key: i }, a.title ) )
					  )
			)
		)
	);
}

function tile( title: string, metric: string, label: string, tone: string ) {
	return createElement(
		'div',
		{ className: 'ngtbm-tile' },
		createElement( 'h3', null, title ),
		createElement( 'div', { className: 'ngtbm-metric' }, metric ),
		createElement(
			'div',
			{ className: 'ngtbm-status', style: { marginTop: 8 } },
			createElement( 'span', { className: 'ngtbm-dot ' + tone, 'aria-hidden': true } ),
			label
		)
	);
}
