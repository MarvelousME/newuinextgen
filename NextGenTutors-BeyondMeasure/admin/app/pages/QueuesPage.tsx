import { createElement, useEffect, useState } from '@wordpress/element';
import { getJson } from '../api/client';

export function QueuesPage() {
	const [ q, setQ ] = useState<{ pending?: number; dlq?: number } | null>( null );
	useEffect( () => {
		getJson<{ pending?: number; dlq?: number }>( 'queues' ).then( setQ ).catch( () => setQ( { pending: 0, dlq: 0 } ) );
	}, [] );
	return createElement(
		'div',
		null,
		createElement( 'h1', { style: { marginTop: 0 } }, 'Queues / DLQ' ),
		createElement(
			'div',
			{ className: 'ngtbm-grid cols-2' },
			createElement(
				'div',
				{ className: 'ngtbm-tile' },
				createElement( 'h3', null, 'Pending' ),
				createElement( 'div', { className: 'ngtbm-metric' }, String( q?.pending ?? '…' ) )
			),
			createElement(
				'div',
				{ className: 'ngtbm-tile' },
				createElement( 'h3', null, 'DLQ' ),
				createElement( 'div', { className: 'ngtbm-metric' }, String( q?.dlq ?? '…' ) )
			)
		)
	);
}
