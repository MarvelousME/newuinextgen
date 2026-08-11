import { createElement, useEffect, useState } from '@wordpress/element';
import { getJson } from '../api/client';

type Matrix = {
	roles: Array<{ id: string; label: string }>;
	capabilities: string[];
	matrix: Record<string, Record<string, boolean>>;
};

export function AccessMatrixPage() {
	const [ data, setData ] = useState<Matrix | null>( null );
	const [ selected, setSelected ] = useState<{ role: string; cap: string } | null>( null );

	useEffect( () => {
		getJson<Matrix>( 'access-matrix' ).then( setData ).catch( () => setData( null ) );
	}, [] );

	if ( ! data ) {
		return createElement( 'div', { className: 'ngtbm-placeholder' }, 'Loading access matrix…' );
	}

	return createElement(
		'div',
		null,
		createElement( 'h1', { style: { marginTop: 0 } }, 'Security → Access Matrix' ),
		createElement(
			'div',
			{ className: 'ngtbm-matrix' },
			createElement(
				'table',
				null,
				createElement(
					'thead',
					null,
					createElement(
						'tr',
						null,
						createElement( 'th', null, 'Capability' ),
						data.roles.map( ( r ) => createElement( 'th', { key: r.id }, r.label.replace( 'NGT ', '' ) ) )
					)
				),
				createElement(
					'tbody',
					null,
					data.capabilities.map( ( cap ) =>
						createElement(
							'tr',
							{ key: cap },
							createElement( 'td', null, cap ),
							data.roles.map( ( r ) =>
								createElement(
									'td',
									{
										key: r.id,
										role: 'button',
										tabIndex: 0,
										onClick: () => setSelected( { role: r.id, cap } ),
										onKeyDown: ( e: { key: string } ) => {
											if ( e.key === 'Enter' ) {
												setSelected( { role: r.id, cap } );
											}
										},
									},
									data.matrix[ r.id ]?.[ cap ] ? '✓' : '—'
								)
							)
						)
					)
				)
			)
		),
		selected
			? createElement(
					'div',
					{ className: 'ngtbm-tile', style: { marginTop: 12 } },
					createElement( 'h3', null, 'Cell detail' ),
					createElement( 'div', null, 'Role: ' + selected.role ),
					createElement( 'div', null, 'Capability: ' + selected.cap ),
					createElement( 'div', null, 'Source: RoleCatalog' ),
					createElement(
						'div',
						null,
						'Effective: ' + ( data.matrix[ selected.role ]?.[ selected.cap ] ? 'ALLOW' : 'DENY' )
					)
			  )
			: null
	);
}
