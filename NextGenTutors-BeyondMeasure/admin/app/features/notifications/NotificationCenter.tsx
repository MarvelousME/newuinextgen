import { createElement, useEffect, useState } from '@wordpress/element';
import { getJson, postJson } from '../../api/client';

type Note = {
	id: number;
	severity: string;
	source: string;
	title: string;
	body: string;
	status: string;
	actionLabel: string;
};

export function NotificationCenter() {
	const [ open, setOpen ] = useState( false );
	const [ notes, setNotes ] = useState<Note[]>( [] );

	const reload = () => getJson<Note[]>( 'notifications' ).then( setNotes ).catch( () => setNotes( [] ) );
	useEffect( () => {
		reload();
	}, [] );

	const openCount = notes.filter( ( n ) => n.status === 'open' ).length;

	return createElement(
		'div',
		{ style: { position: 'relative' } },
		createElement(
			'button',
			{ type: 'button', className: 'ngtbm-btn', onClick: () => setOpen( ( v ) => ! v ), 'aria-label': 'Notifications' },
			`Notifications (${ openCount })`
		),
		open
			? createElement(
					'div',
					{
						className: 'ngtbm-tile',
						style: { position: 'absolute', right: 0, top: 40, width: 360, zIndex: 20, maxHeight: 420, overflow: 'auto' },
					},
					createElement( 'strong', null, 'Notification center' ),
					notes.map( ( n ) =>
						createElement(
							'div',
							{ key: n.id, style: { borderTop: '1px solid var(--ngtbm-border)', paddingTop: 8, marginTop: 8 } },
							createElement( 'div', null, `${ n.severity } · ${ n.source }` ),
							createElement( 'div', null, n.title ),
							createElement( 'div', { style: { color: 'var(--ngtbm-muted)', fontSize: 12 } }, n.body ),
							n.status === 'open'
								? createElement(
										'button',
										{
											type: 'button',
											className: 'ngtbm-btn',
											style: { marginTop: 6 },
											onClick: () => postJson( `notifications/${ n.id }/ack`, {} ).then( reload ),
										},
										n.actionLabel || 'Acknowledge'
								  )
								: createElement( 'span', { style: { fontSize: 12, color: 'var(--ngtbm-muted)' } }, 'Acknowledged' )
						)
					)
			  )
			: null
	);
}
