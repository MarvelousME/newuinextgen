import { createElement, useEffect, useState, type ReactNode } from '@wordpress/element';
import { getJson } from '../api/client';
import type { BootConfig } from '../api/types';
import { NotificationCenter } from '../features/notifications/NotificationCenter';

type NavItem = {
	id: string;
	label: string;
	path?: string;
	children?: NavItem[];
	placeholder?: boolean;
};

type Props = {
	path: string;
	navigate: ( path: string ) => void;
	page: ReactNode;
	boot: BootConfig;
};

export function AppShell( { path, navigate, page, boot }: Props ) {
	const [ nav, setNav ] = useState<NavItem[]>( [] );

	useEffect( () => {
		getJson<NavItem[]>( 'nav' ).then( setNav ).catch( () => setNav( [] ) );
	}, [] );

	return createElement(
		'div',
		{ className: 'ngtbm-app' },
		createElement(
			'aside',
			{ className: 'ngtbm-sidebar', 'aria-label': 'Beyond Measure navigation' },
			createElement(
				'div',
				{ className: 'ngtbm-brand' },
				'Next Gen Tutors',
				createElement( 'span', null, 'Beyond Measure v' + boot.version )
			),
			nav.map( ( item ) =>
				createElement(
					'div',
					{ key: item.id, className: 'ngtbm-nav-group' },
					createElement( 'div', { className: 'ngtbm-nav-label' }, item.label ),
					item.path
						? createElement(
								'button',
								{
									type: 'button',
									className: 'ngtbm-nav-item' + ( path === item.path ? ' is-active' : '' ),
									onClick: () => navigate( item.path! ),
								},
								item.label
						  )
						: null,
					( item.children || [] ).map( ( child ) =>
						createElement(
							'button',
							{
								key: child.id,
								type: 'button',
								className: 'ngtbm-nav-item' + ( path === child.path ? ' is-active' : '' ),
								onClick: () => child.path && navigate( child.path ),
							},
							child.label
						)
					)
				)
			)
		),
		createElement(
			'div',
			{ className: 'ngtbm-main' },
			createElement(
				'header',
				{ className: 'ngtbm-topbar' },
				createElement( 'div', null, 'Control Plane' ),
				createElement( NotificationCenter )
			),
			createElement( 'main', { className: 'ngtbm-content' }, page )
		)
	);
}
