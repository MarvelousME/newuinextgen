import { createElement, useEffect, useState } from '@wordpress/element';
import { getJson } from '../api/client';

export function AuditPage() {
	const [ rows, setRows ] = useState<any[]>( [] );
	useEffect( () => {
		getJson<any[]>( 'audit' ).then( setRows ).catch( () => setRows( [] ) );
	}, [] );
	return createElement(
		'div',
		null,
		createElement( 'h1', { style: { marginTop: 0 } }, 'Governance → Audit' ),
		createElement(
			'div',
			{ className: 'ngtbm-tile' },
			createElement(
				'table',
				{ className: 'ngtbm-table' },
				createElement( 'thead', null, createElement( 'tr', null, createElement( 'th', null, 'When' ), createElement( 'th', null, 'Action' ), createElement( 'th', null, 'Resource' ), createElement( 'th', null, 'Actor' ) ) ),
				createElement(
					'tbody',
					null,
					rows.map( ( r ) =>
						createElement( 'tr', { key: r.id }, createElement( 'td', null, r.createdAt ), createElement( 'td', null, r.action ), createElement( 'td', null, `${ r.resource }:${ r.resourceId }` ), createElement( 'td', null, String( r.actorId ) ) )
					)
				)
			)
		)
	);
}
