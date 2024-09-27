<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Integrations\GenericHttp\GenericHttpDatasource;
use RemoteDataBlocks\Logging\LoggerManager;

require_once __DIR__ . '/inc/queries/class-get-zip-code-query.php';

function register_zipcode_block() {
	$zipcode_datasource = GenericHttpDatasource::from_slug( 'zip-code' );

	if ( !$zipcode_datasource instanceof GenericHttpDatasource ) {
		LoggerManager::instance()->debug( 'Zip Code datasource not found' );
		return;
	}

	$zipcode_query = new GetZipCodeQuery( $zipcode_datasource );

	register_remote_data_block( 'Zip Code', $zipcode_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_zipcode_block' );
