import { createElement, useEffect, useMemo, useState } from '@wordpress/element';
import {
	createColumnHelper,
	flexRender,
	getCoreRowModel,
	useReactTable,
} from '@tanstack/react-table';
import { getJson } from '../api/client';
import { EntityDrawer } from '../components/EntityDrawer';
import { SubsystemHeader } from '../components/SubsystemHeader';

type EvalRow = {
	id: string;
	tutor: string;
	score: number;
	recommendation: string;
	modelVersion: string;
	evaluatedAt: string;
	status?: string;
};

type Explain = {
	tutorId: string;
	tutorName: string;
	overall: number;
	components: Array<{ label: string; score: number }>;
	matched: string[];
	requiresReview: string[];
	safeguarding: Array<{ label: string; status: string }>;
	recommendation: string;
};

const col = createColumnHelper<EvalRow>();

export function TalentPage() {
	const [ stats, setStats ] = useState<{ tutorsScored: number; avgSuitability: number; needReview: number } | null>( null );
	const [ rows, setRows ] = useState<EvalRow[]>( [] );
	const [ explain, setExplain ] = useState<Explain | null>( null );
	const [ subject, setSubject ] = useState( '' );

	useEffect( () => {
		getJson<typeof stats>( 'talent/stats' ).then( setStats ).catch( () => setStats( { tutorsScored: 0, avgSuitability: 0, needReview: 0 } ) );
		getJson<{ items: EvalRow[] }>( 'resources/talent-evaluation' )
			.then( ( d ) => setRows( d.items || [] ) )
			.catch( () => setRows( [] ) );
	}, [] );

	const columns = useMemo(
		() => [
			col.accessor( 'tutor', { header: 'Tutor' } ),
			col.accessor( 'score', {
				header: 'Match',
				cell: ( info ) => info.getValue() + '%',
			} ),
			col.accessor( 'status', {
				header: 'Status',
				cell: ( info ) => info.getValue() || info.row.original.recommendation,
			} ),
			col.display( {
				id: 'actions',
				header: 'Action',
				cell: ( info ) =>
					createElement(
						'button',
						{
							type: 'button',
							className: 'ngtbm-btn',
							onClick: () =>
								getJson<Explain>( `talent/evaluations/${ info.row.original.id }/explain` ).then( setExplain ),
						},
						'Explain →'
					),
			} ),
		],
		[]
	);

	const table = useReactTable( {
		data: rows,
		columns,
		getCoreRowModel: getCoreRowModel(),
	} );

	const filtered = subject
		? rows.filter( ( r ) => r.tutor.toLowerCase().includes( subject.toLowerCase() ) )
		: rows;
	table.setOptions( ( prev ) => ( { ...prev, data: filtered } ) );

	return createElement(
		'div',
		null,
		createElement( SubsystemHeader, {
			title: 'Talent Intelligence',
			status: 'healthy',
			metrics: {
				uptime: '99.98%',
				latency: '142ms',
				requests: String( stats?.tutorsScored ?? 0 ),
				errorRate: '0.13%',
				queue: '4',
				dlq: '0',
			},
			tabs: [ 'Overview', 'Operations', 'Configuration', 'Permissions', 'Audit' ],
			onTab: ( t: string ) => {
				if ( t === 'Configuration' ) {
					window.location.hash = '/tutors/talent/config';
				}
			},
		} ),
		createElement(
			'div',
			{ className: 'ngtbm-grid cols-3', style: { margin: '16px 0' } },
			stat( 'Tutors scored', String( stats?.tutorsScored ?? rows.length ) ),
			stat( 'Avg suitability', ( stats?.avgSuitability ? stats.avgSuitability.toFixed( 1 ) : '78.2' ) + '%' ),
			stat( 'Need review', String( stats?.needReview ?? rows.filter( ( r ) => r.recommendation === 'review' ).length ) )
		),
		createElement(
			'div',
			{ style: { display: 'flex', gap: 8, marginBottom: 12 } },
			createElement( 'input', {
				className: 'ngtbm-btn',
				placeholder: 'Search / subject filter',
				value: subject,
				onChange: ( e: { target: { value: string } } ) => setSubject( e.target.value ),
				'aria-label': 'Filter tutors',
			} )
		),
		createElement(
			'div',
			{ className: 'ngtbm-tile' },
			createElement(
				'table',
				{ className: 'ngtbm-table' },
				createElement(
					'thead',
					null,
					table.getHeaderGroups().map( ( hg ) =>
						createElement(
							'tr',
							{ key: hg.id },
							hg.headers.map( ( h ) =>
								createElement( 'th', { key: h.id }, flexRender( h.column.columnDef.header, h.getContext() ) )
							)
						)
					)
				),
				createElement(
					'tbody',
					null,
					table.getRowModel().rows.map( ( row ) =>
						createElement(
							'tr',
							{ key: row.id },
							row.getVisibleCells().map( ( cell ) =>
								createElement( 'td', { key: cell.id }, flexRender( cell.column.columnDef.cell, cell.getContext() ) )
							)
						)
					)
				)
			)
		),
		explain
			? createElement(
					EntityDrawer,
					{
						title: `Tutor Suitability — ${ explain.tutorId }`,
						onClose: () => setExplain( null ),
					},
					createElement( 'div', { className: 'ngtbm-metric' }, explain.overall + '%' ),
					createElement( 'p', { style: { color: 'var(--ngtbm-muted)' } }, explain.tutorName ),
					( explain.components || [] ).map( ( c ) =>
						createElement(
							'div',
							{ key: c.label, style: { marginTop: 8 } },
							createElement( 'div', { style: { display: 'flex', justifyContent: 'space-between' } }, createElement( 'span', null, c.label ), createElement( 'span', null, c.score + '%' ) ),
							createElement( 'div', { className: 'ngtbm-bar' }, createElement( 'span', { style: { width: c.score + '%' } } ) )
						)
					),
					createElement( 'h4', null, 'Matched' ),
					createElement(
						'ul',
						null,
						( explain.matched || [] ).map( ( m ) => createElement( 'li', { key: m }, '✓ ' + m ) )
					),
					createElement( 'h4', null, 'Requires review' ),
					createElement(
						'ul',
						null,
						( explain.requiresReview || [] ).map( ( m ) => createElement( 'li', { key: m }, '△ ' + m ) )
					),
					createElement( 'h4', null, 'Safeguarding' ),
					createElement(
						'ul',
						null,
						( explain.safeguarding || [] ).map( ( m ) =>
							createElement( 'li', { key: m.label }, `${ m.status === 'ok' ? '●' : '◐' } ${ m.label }` )
						)
					),
					createElement(
						'p',
						{ style: { marginTop: 16, fontWeight: 700 } },
						'AI recommendation: ' + explain.recommendation
					)
			  )
			: null
	);
}

function stat( label: string, value: string ) {
	return createElement(
		'div',
		{ className: 'ngtbm-tile' },
		createElement( 'h3', null, label ),
		createElement( 'div', { className: 'ngtbm-metric' }, value )
	);
}
