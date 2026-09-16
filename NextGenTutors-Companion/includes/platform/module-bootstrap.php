<?php
/**
 * Platform module bootstrap stub.
 *
 * Named module-bootstrap.php to avoid clashing with existing platform files.
 * No-op: platform classes continue to load via the existing plugin bootstrap / autoloader.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Marker for NGC_Module_Registry lazy-load; Agents G–J will wire domain init here.
return true;
