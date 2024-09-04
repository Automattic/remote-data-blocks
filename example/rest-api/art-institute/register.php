<?php

namespace RemoteDataBlocks\Example\ArtInstituteOfChicago;

use RemoteDataBlocks\Config\HttpDatasource;
use RemoteDataBlocks\Editor\ConfigurationLoader;

require_once __DIR__ . '/inc/queries/class-art-institute-get-query.php';
require_once __DIR__ . '/inc/queries/class-art-institute-search-query.php';

function register_aic_block() {
	$config = [
		'friendly_name'   => 'Art Institute of Chicago',
		'uid'             => 'aic_artworks',
		'endpoint'        => 'https://api.artic.edu/api/v1/artworks',
		'request_headers' => [
			'Content-Type' => 'application/json',
		],
	];

	$aic_datasource   = new HttpDatasource( $config );
	$get_art_query    = new ArtInstituteOfChicagoGetArtQuery( $aic_datasource );
	$search_art_query = new ArtInstituteOfChicagoSearchArtQuery( $aic_datasource );

	ConfigurationLoader::register_block( 'Art Institute of Chicago', $get_art_query );
	ConfigurationLoader::register_search_query( 'Art Institute of Chicago', $search_art_query );
}
add_action( 'register_remote_data_blocks', __NAMESPACE__ . '\\register_aic_block' );
