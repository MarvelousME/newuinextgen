<?php
/**
 * Metadata-driven CRUD helpers (detail/forms via entity registry).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates and routes entity writes through domain callbacks.
 */
final class NGC_Admin_Crud {

	/**
	 * List rows for entity.
	 *
	 * @param string               $key  Entity key.
	 * @param array<string, mixed> $args Query.
	 * @return array{ok:bool,rows?:array,total?:int,message?:string}
	 */
	public static function list_items( $key, array $args = [] ) {
		$entity = NGC_Admin_Entity_Registry::get( $key );
		if ( ! $entity ) {
			return [ 'ok' => false, 'message' => 'unknown_entity' ];
		}
		if ( ! self::can( $entity ) ) {
			return [ 'ok' => false, 'message' => 'forbidden' ];
		}
		$cb = $entity['list_callback'] ?? null;
		if ( ! is_callable( $cb ) ) {
			return [ 'ok' => false, 'message' => 'no_list' ];
		}
		$result = call_user_func( $cb, $args );
		return [
			'ok'    => true,
			'rows'  => (array) ( $result['rows'] ?? [] ),
			'total' => (int) ( $result['total'] ?? 0 ),
		];
	}

	/**
	 * @param string $key Entity.
	 * @param int    $id  ID.
	 * @return array{ok:bool,item?:array,message?:string}
	 */
	public static function get_item( $key, $id ) {
		$entity = NGC_Admin_Entity_Registry::get( $key );
		if ( ! $entity || ! self::can( $entity ) ) {
			return [ 'ok' => false, 'message' => 'forbidden' ];
		}
		$cb = $entity['get_callback'] ?? null;
		if ( ! is_callable( $cb ) ) {
			return [ 'ok' => false, 'message' => 'no_get' ];
		}
		$item = call_user_func( $cb, (int) $id );
		if ( ! $item ) {
			return [ 'ok' => false, 'message' => 'not_found' ];
		}
		return [ 'ok' => true, 'item' => $item ];
	}

	/**
	 * @param string               $key  Entity.
	 * @param int                  $id   ID.
	 * @param array<string, mixed> $data Payload.
	 * @return array{ok:bool,item?:array,message?:string,errors?:array}
	 */
	public static function update_item( $key, $id, array $data ) {
		$entity = NGC_Admin_Entity_Registry::get( $key );
		if ( ! $entity || ! self::can( $entity ) ) {
			return [ 'ok' => false, 'message' => 'forbidden' ];
		}
		$errors = self::validate( $entity, $data, false );
		if ( $errors ) {
			return [ 'ok' => false, 'errors' => $errors ];
		}
		$cb = $entity['update_callback'] ?? null;
		if ( ! is_callable( $cb ) ) {
			return [ 'ok' => false, 'message' => 'no_update' ];
		}
		$item = call_user_func( $cb, (int) $id, $data );
		if ( class_exists( 'NGC_Audit' ) && method_exists( 'NGC_Audit', 'log' ) ) {
			NGC_Audit::log( 'admin_crud_update', [ 'entity' => $key, 'id' => (int) $id ] );
		}
		return [ 'ok' => true, 'item' => $item ];
	}

	/**
	 * @param string $key Entity.
	 * @param int    $id  ID.
	 * @return array{ok:bool,message?:string}
	 */
	public static function delete_item( $key, $id ) {
		$entity = NGC_Admin_Entity_Registry::get( $key );
		if ( ! $entity || ! self::can( $entity ) ) {
			return [ 'ok' => false, 'message' => 'forbidden' ];
		}
		$cb = $entity['delete_callback'] ?? null;
		if ( ! is_callable( $cb ) ) {
			return [ 'ok' => false, 'message' => 'no_delete' ];
		}
		$ok = (bool) call_user_func( $cb, (int) $id );
		return [ 'ok' => $ok ];
	}

	/**
	 * Render detail/edit form HTML for an item.
	 *
	 * @param string               $key  Entity.
	 * @param array<string, mixed> $item Item.
	 * @return string
	 */
	public static function render_detail_html( $key, array $item ) {
		$entity = NGC_Admin_Entity_Registry::get( $key );
		if ( ! $entity ) {
			return '';
		}
		ob_start();
		echo '<form class="ngt-admin-crud-form" data-entity="' . esc_attr( $key ) . '" data-id="' . esc_attr( (string) ( $item['id'] ?? '' ) ) . '">';
		foreach ( (array) $entity['fields'] as $field ) {
			$fkey = (string) ( $field['key'] ?? '' );
			$val  = $item[ $fkey ] ?? '';
			$type = (string) ( $field['type'] ?? 'text' );
			echo '<label class="ngt-admin-field"><span>' . esc_html( (string) ( $field['label'] ?? $fkey ) ) . '</span>';
			if ( 'textarea' === $type ) {
				echo '<textarea name="' . esc_attr( $fkey ) . '">' . esc_textarea( (string) $val ) . '</textarea>';
			} elseif ( 'select' === $type ) {
				echo '<select name="' . esc_attr( $fkey ) . '">';
				foreach ( (array) ( $field['options'] ?? [] ) as $opt ) {
					printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( (string) $opt ), selected( (string) $val, (string) $opt, false ) );
				}
				echo '</select>';
			} else {
				printf( '<input type="%1$s" name="%2$s" value="%3$s" />', esc_attr( $type ), esc_attr( $fkey ), esc_attr( (string) $val ) );
			}
			echo '</label>';
		}
		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Save', 'nextgencompanion' ) . '</button></p>';
		echo '</form>';
		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $entity Entity.
	 * @return bool
	 */
	private static function can( array $entity ) {
		$cap = (string) ( $entity['capability'] ?? 'manage_options' );
		return current_user_can( $cap ) || current_user_can( 'manage_options' );
	}

	/**
	 * @param array<string, mixed> $entity Entity.
	 * @param array<string, mixed> $data   Data.
	 * @param bool                 $create Creating.
	 * @return array<int, string>
	 */
	private static function validate( array $entity, array $data, $create ) {
		unset( $create );
		$errors = [];
		foreach ( (array) $entity['fields'] as $field ) {
			$key = (string) ( $field['key'] ?? '' );
			if ( ! empty( $field['required'] ) && array_key_exists( $key, $data ) && '' === trim( (string) $data[ $key ] ) ) {
				$errors[] = $key . '_required';
			}
		}
		return $errors;
	}
}
