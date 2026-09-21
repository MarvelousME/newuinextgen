<?php
/**
 * Search form (html5).
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="ngt-s"><?php esc_html_e( 'Search for:', 'nextgen-tutors' ); ?></label>
	<input type="search" id="ngt-s" class="search-field" placeholder="<?php esc_attr_e( 'Search…', 'nextgen-tutors' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
	<button type="submit" class="search-submit"><?php esc_html_e( 'Search', 'nextgen-tutors' ); ?></button>
</form>
