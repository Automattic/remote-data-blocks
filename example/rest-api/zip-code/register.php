<?php

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;

require_once __DIR__ . '/inc/queries/class-zip-code-datasource.php';
require_once __DIR__ . '/inc/queries/class-get-zip-code-query.php';

function register_zipcode_block() {
	$zipcode_datasource = ZipCodeDatasource::from_array( [] );
	$zipcode_query      = new GetZipCodeQuery( $zipcode_datasource );

	register_remote_data_block( 'Zip Code', $zipcode_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_zipcode_block' );
