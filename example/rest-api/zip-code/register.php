<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

require_once __DIR__ . '/inc/queries/class-zip-code-data-source.php';
require_once __DIR__ . '/inc/queries/class-get-zip-code-query.php';

function register_zipcode_block() {
	$zipcode_data_source = ZipCodeDataSource::from_array( [
		'slug'    => 'zip-code',
		'service' => 'zip-code',
	] );
	
	$zipcode_query = new GetZipCodeQuery( $zipcode_data_source );

	register_remote_data_block( 'Zip Code', $zipcode_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_zipcode_block' );
