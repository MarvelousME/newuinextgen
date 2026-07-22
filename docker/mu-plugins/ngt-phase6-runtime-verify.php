<?php
/**
 * Temporary: force production defaults for Phase 6 runtime verification.
 * Mount or copy into wp-content/mu-plugins/ on Docker staging.
 */
add_filter( 'bi_use_prototype_blend', '__return_false', 99 );
