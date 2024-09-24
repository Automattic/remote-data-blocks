<?php declare(strict_types = 1);

add_filter('home_url', function ( $url, $path ) {
	if ( have_posts() && is_singular() ) {
		return site_url( $path );
	}

	if ( wp_is_json_request() ) {
		return site_url( $path );
	}

	return $url;
}, 10, 2);

// temporary
define( 'WPCOMVIP__BLOCK_DATA_API__PARSE_TIME_ERROR_MS', 5000 );
