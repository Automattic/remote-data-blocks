<?php

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

require_once __DIR__ . '/inc/queries/class-art-institute-datasource.php';
require_once __DIR__ . '/inc/queries/class-art-institute-get-query.php';
require_once __DIR__ . '/inc/queries/class-art-institute-search-query.php';

function register_aic_block() {
	$aic_datasource   = ArtInstituteOfChicagoDatasource::from_array( [] );
	$get_art_query    = new ArtInstituteOfChicagoGetArtQuery( $aic_datasource );
	$search_art_query = new ArtInstituteOfChicagoSearchArtQuery( $aic_datasource );

	register_remote_data_block( 'Art Institute of Chicago', $get_art_query );
	register_remote_data_search_query( 'Art Institute of Chicago', $search_art_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_aic_block' );
