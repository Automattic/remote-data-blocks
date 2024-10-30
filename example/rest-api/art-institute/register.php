<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

require_once __DIR__ . '/inc/queries/class-art-institute-data-source.php';
require_once __DIR__ . '/inc/queries/class-art-institute-get-query.php';
require_once __DIR__ . '/inc/queries/class-art-institute-search-query.php';

function register_aic_block() {
	$aic_data_source = ArtInstituteOfChicagoDataSource::from_array( [
		'slug' => 'art-institute-of-chicago',
		'service' => 'art-institute-of-chicago',
	] );

	$get_art_query = new ArtInstituteOfChicagoGetArtQuery( $aic_data_source );
	$search_art_query = new ArtInstituteOfChicagoSearchArtQuery( $aic_data_source );

	register_remote_data_block( 'Art Institute of Chicago', $get_art_query );
	register_remote_data_search_query( 'Art Institute of Chicago', $search_art_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_aic_block' );
