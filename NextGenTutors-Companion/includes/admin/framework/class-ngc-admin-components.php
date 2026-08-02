<?php
/**
 * Live Admin UI Component metadata (derived from implementation, no JSON catalog drift).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Component library for administration chrome.
 */
final class NGC_Admin_Components {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function catalog() {
		$items = [
			[
				'id'       => 'button',
				'label'    => 'Button',
				'variants' => [ 'primary', 'secondary', 'link' ],
				'preview'  => '<button type="button" class="button button-primary ngt-admin-btn">Primary</button> <button type="button" class="button">Secondary</button>',
			],
			[
				'id'       => 'card',
				'label'    => 'Card',
				'variants' => [ 'default' ],
				'preview'  => '<div class="ngt-admin-card" data-ngt-motion><strong>Card</strong><p>Token-driven surface.</p></div>',
			],
			[
				'id'       => 'badge',
				'label'    => 'Badge',
				'variants' => [ 'info', 'success', 'warning', 'error' ],
				'preview'  => '<span class="ngt-admin-badge">Info</span> <span class="ngt-admin-badge is-success">Success</span>',
			],
			[
				'id'       => 'tabs',
				'label'    => 'Tabs',
				'variants' => [ 'default' ],
				'preview'  => '<div class="ngt-admin-tabs"><button type="button" class="is-active">Overview</button><button type="button">Details</button></div>',
			],
			[
				'id'       => 'toast',
				'label'    => 'Toast',
				'variants' => [ 'success', 'warning', 'error' ],
				'preview'  => '<div class="ngt-admin-toast">Saved successfully</div>',
			],
			[
				'id'       => 'drawer',
				'label'    => 'Drawer',
				'variants' => [ 'right' ],
				'preview'  => '<div class="ngt-admin-drawer-preview">Drawer panel preview</div>',
			],
			[
				'id'       => 'datagrid',
				'label'    => 'Data Grid',
				'variants' => [ 'default' ],
				'preview'  => '<div class="ngt-admin-card">Enterprise grid mount (see Applications / Matches / Safeguarding)</div>',
			],
			[
				'id'       => 'form',
				'label'    => 'Form controls',
				'variants' => [ 'text', 'select' ],
				'preview'  => '<label class="ngt-admin-field"><span>Label</span><input type="text" value="Sample" /></label>',
			],
		];
		return apply_filters( 'ngt_admin_components', $items );
	}

	/**
	 * Component library screen.
	 */
	public static function render_library() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		NGC_Admin_Layout::render_page(
			[
				'title'   => __( 'Admin UI Component Library', 'nextgencompanion' ),
				'summary' => __( 'Live previews of reusable administration components. Metadata is derived from the component provider — not a separate JSON catalog.', 'nextgencompanion' ),
				'content' => static function () {
					echo '<div class="ngt-admin-component-lib" data-testid="ngt-admin-component-lib">';
					foreach ( self::catalog() as $item ) {
						echo '<section class="ngt-admin-card ngt-admin-component" data-ngt-motion data-component="' . esc_attr( (string) $item['id'] ) . '">';
						echo '<header><h3>' . esc_html( (string) $item['label'] ) . '</h3>';
						echo '<code>' . esc_html( (string) $item['id'] ) . '</code></header>';
						echo '<div class="ngt-admin-component__preview">';
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted internal previews.
						echo $item['preview'];
						echo '</div>';
						echo '<p class="description">Variants: ' . esc_html( implode( ', ', (array) ( $item['variants'] ?? [] ) ) ) . '</p>';
						echo '</section>';
					}
					echo '</div>';
				},
			]
		);
	}
}
