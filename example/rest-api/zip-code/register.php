<?php

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Editor\ConfigurationLoader;

require_once __DIR__ . '/inc/queries/class-zip-code-datasource.php';
require_once __DIR__ . '/inc/queries/class-get-zip-code-query.php';

function register_zipcode_block() {
	$zipcode_datasource = new ZipCodeDatasource();
	$zipcode_query      = new GetZipCodeQuery( $zipcode_datasource );

	ConfigurationLoader::register_block( 'Zip Code', $zipcode_query );
}
add_action( 'register_remote_data_blocks', __NAMESPACE__ . '\\register_zipcode_block' );
