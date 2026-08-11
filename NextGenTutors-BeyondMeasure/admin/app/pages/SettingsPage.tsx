import { createElement } from '@wordpress/element';

export function SettingsPage() {
	return createElement(
		'div',
		{ className: 'ngtbm-placeholder' },
		createElement( 'h1', { style: { marginTop: 0, color: 'var(--ngtbm-text)' } }, 'Settings' ),
		createElement(
			'p',
			null,
			'Control Plane settings are schema-driven per subsystem. Open Talent Intelligence → Configuration for the first fully wired example.'
		)
	);
}
