import { createElement, type ReactNode } from '@wordpress/element';

type Props = {
	title: string;
	onClose: () => void;
	children?: ReactNode;
};

export function EntityDrawer( { title, onClose, children }: Props ) {
	return createElement(
		'aside',
		{ className: 'ngtbm-drawer', role: 'dialog', 'aria-label': title },
		createElement(
			'div',
			{ style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
			createElement( 'h2', { style: { margin: 0, fontSize: 16 } }, title ),
			createElement( 'button', { type: 'button', className: 'ngtbm-btn', onClick: onClose, 'aria-label': 'Close' }, '×' )
		),
		createElement( 'div', { style: { marginTop: 16 } }, children )
	);
}
