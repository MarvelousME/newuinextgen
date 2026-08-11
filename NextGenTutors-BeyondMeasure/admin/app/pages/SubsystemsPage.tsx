import { createElement, useEffect, useState } from '@wordpress/element';
import { getJson, postJson } from '../api/client';

type Sub = {
	id: string;
	name: string;
	category: string;
	status: string;
	enabled: boolean;
	capabilities: string[];
	legacyAdminUrl?: string;
};

export function SubsystemsPage() {
	const [ items, setItems ] = useState<Sub[]>( [] );
	const [ impact, setImpact ] = useState<null | { subsystem: string; affected: string[]; notAffected: string[] }>( null );

	const reload = () => getJson<Sub[]>( 'subsystems' ).then( setItems ).catch( () => setItems( [] ) );
	useEffect( () => {
		reload();
	}, [] );

	const disable = async ( id: string ) => {
		const res = await postJson<{ requiresConfirmation?: boolean; impact?: { affected: string[]; notAffected: string[] }; subsystem?: Sub }>(
			`subsystems/${ id }/disable`,
			{}
		);
		if ( res.requiresConfirmation && res.impact ) {
			setImpact( { subsystem: id, ...res.impact } );
			return;
		}
		reload();
	};

	const confirmDisable = async () => {
		if ( ! impact ) {
			return;
		}
		await postJson( `subsystems/${ impact.subsystem }/disable`, { mode: 'drain' } );
		setImpact( null );
		reload();
	};

	return createElement(
		'div',
		null,
		createElement( 'h1', { style: { marginTop: 0 } }, 'Ecosystem → Subsystems' ),
		createElement(
			'div',
			{ className: 'ngtbm-grid cols-2' },
			items.map( ( s ) =>
				createElement(
					'article',
					{ key: s.id, className: 'ngtbm-tile' },
					createElement(
						'div',
						{ style: { display: 'flex', justifyContent: 'space-between' } },
						createElement( 'strong', null, s.name ),
						createElement(
							'span',
							{ className: 'ngtbm-status' },
							createElement( 'span', { className: 'ngtbm-dot ' + ( s.enabled ? '' : 'crit' ) } ),
							s.enabled ? s.status : 'offline'
						)
					),
					createElement( 'div', { style: { color: 'var(--ngtbm-muted)', marginTop: 6 } }, s.category ),
					createElement( 'div', { style: { marginTop: 8, fontSize: 12 } }, ( s.capabilities || [] ).slice( 0, 4 ).join( ', ' ) ),
					createElement(
						'div',
						{ style: { display: 'flex', gap: 8, marginTop: 12 } },
						s.enabled
							? createElement( 'button', { type: 'button', className: 'ngtbm-btn danger', onClick: () => disable( s.id ) }, 'Disable' )
							: createElement(
									'button',
									{
										type: 'button',
										className: 'ngtbm-btn primary',
										onClick: () => postJson( `subsystems/${ s.id }/enable`, {} ).then( reload ),
									},
									'Enable'
							  ),
						s.legacyAdminUrl
							? createElement( 'a', { className: 'ngtbm-btn', href: s.legacyAdminUrl }, 'Legacy admin' )
							: null
					)
				)
			)
		),
		impact
			? createElement(
					'div',
					{ className: 'ngtbm-drawer' },
					createElement( 'h2', null, `Disable ${ impact.subsystem }?` ),
					createElement( 'h4', null, 'Affected' ),
					createElement(
						'ul',
						null,
						impact.affected.map( ( a ) => createElement( 'li', { key: a }, a ) )
					),
					createElement( 'h4', null, 'Not affected' ),
					createElement(
						'ul',
						null,
						impact.notAffected.map( ( a ) => createElement( 'li', { key: a }, '✓ ' + a ) )
					),
					createElement(
						'div',
						{ style: { display: 'flex', gap: 8, marginTop: 16 } },
						createElement( 'button', { type: 'button', className: 'ngtbm-btn', onClick: () => setImpact( null ) }, 'Cancel' ),
						createElement( 'button', { type: 'button', className: 'ngtbm-btn danger', onClick: confirmDisable }, 'Disable' )
					)
			  )
			: null
	);
}
