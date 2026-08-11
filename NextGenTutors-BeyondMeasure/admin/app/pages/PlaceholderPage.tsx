import { createElement } from '@wordpress/element';

export function PlaceholderPage( { path }: { path: string } ) {
	return createElement(
		'div',
		{ className: 'ngtbm-placeholder' },
		createElement( 'h1', { style: { marginTop: 0, color: 'var(--ngtbm-text)' } }, path ),
		createElement(
			'p',
			null,
			'This Control Plane section is registered in the navigation IA. Domain data sources can plug in via subsystem registration without reinventing CRUD, RBAC, health, or audit.'
		),
		createElement( 'p', null, 'Status: structured placeholder · Health stub: unknown · Registered providers: 0' )
	);
}
