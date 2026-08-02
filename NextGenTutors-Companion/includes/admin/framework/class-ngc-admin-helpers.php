<?php
/**
 * Helper: unified admin parent slug for all NextGen plugins.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ngt_admin_parent' ) ) {
	/**
	 * Parent menu slug for NEXT GEN TUTORS shell.
	 *
	 * @return string
	 */
	function ngt_admin_parent() {
		if ( class_exists( 'NGC_Admin_Shell' ) ) {
			return NGC_Admin_Shell::PARENT_SLUG;
		}
		return 'ngt-admin';
	}
}

if ( ! function_exists( 'ngt_admin_register_screen' ) ) {
	/**
	 * Register a screen with the central admin registry.
	 *
	 * @param array<string, mixed> $screen Screen definition.
	 */
	function ngt_admin_register_screen( array $screen ) {
		if ( class_exists( 'NGC_Admin_Registry' ) ) {
			NGC_Admin_Registry::register_screen( $screen );
		}
	}
}

if ( ! function_exists( 'ngt_admin_register_module' ) ) {
	/**
	 * Register a module with the central admin registry.
	 *
	 * @param array<string, mixed> $module Module definition.
	 */
	function ngt_admin_register_module( array $module ) {
		if ( class_exists( 'NGC_Admin_Registry' ) ) {
			NGC_Admin_Registry::register_module( $module );
		}
	}
}

if ( ! function_exists( 'ngt_admin_register_entity' ) ) {
	/**
	 * @param array<string, mixed> $entity Entity definition.
	 */
	function ngt_admin_register_entity( array $entity ) {
		if ( class_exists( 'NGC_Admin_Entity_Registry' ) ) {
			NGC_Admin_Entity_Registry::register( $entity );
		}
	}
}

if ( ! function_exists( 'ngt_admin_display_title' ) ) {
	/**
	 * @return string
	 */
	function ngt_admin_display_title() {
		if ( class_exists( 'NGC_Platform_Version' ) ) {
			return NGC_Platform_Version::display_title();
		}
		return 'NEXT GEN TUTORS v1.0';
	}
}
