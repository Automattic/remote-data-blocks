<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Integrations\GenericHttp\GenericHttpDataSource;
use RemoteDataBlocks\Logging\LoggerManager;

require_once __DIR__ . '/inc/queries/class-get-zip-code-query.php';

function register_zipcode_block() {
	$zipcode_data_source = GenericHttpDataSource::from_slug( 'zip-code' );

	if ( ! $zipcode_data_source instanceof GenericHttpDataSource ) {
		LoggerManager::instance()->debug( 'Zip Code data source not found' );
		return;
	}

	$zipcode_query = new GetZipCodeQuery( $zipcode_data_source );

	register_remote_data_block( 'Zip Code', $zipcode_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_zipcode_block' );
