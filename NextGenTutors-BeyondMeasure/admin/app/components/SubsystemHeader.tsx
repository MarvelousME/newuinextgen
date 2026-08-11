import { createElement } from '@wordpress/element';

type Props = {
	title: string;
	status: string;
	metrics: Record<string, string>;
	tabs: string[];
	onTab?: ( tab: string ) => void;
};

export function SubsystemHeader( { title, status, metrics, tabs, onTab }: Props ) {
	return createElement(
		'header',
		{ className: 'ngtbm-tile' },
		createElement(
			'div',
			{ style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
			createElement( 'h1', { style: { margin: 0, fontSize: 20 } }, title ),
			createElement(
				'div',
				{ className: 'ngtbm-status' },
				createElement( 'span', { className: 'ngtbm-dot', 'aria-hidden': true } ),
				status.toUpperCase()
			)
		),
		createElement(
			'div',
			{ className: 'ngtbm-grid cols-4', style: { marginTop: 12 } },
			Object.entries( metrics ).map( ( [ k, v ] ) =>
				createElement(
					'div',
					{ key: k },
					createElement( 'div', { style: { color: 'var(--ngtbm-muted)', fontSize: 12 } }, k ),
					createElement( 'strong', null, v )
				)
			)
		),
		createElement(
			'div',
			{ style: { display: 'flex', gap: 8, marginTop: 14, flexWrap: 'wrap' } },
			tabs.map( ( t ) =>
				createElement(
					'button',
					{ key: t, type: 'button', className: 'ngtbm-btn', onClick: () => onTab?.( t ) },
					t
				)
			)
		)
	);
}
