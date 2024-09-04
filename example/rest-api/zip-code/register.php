<?php

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\HttpDatasource;
use RemoteDataBlocks\Editor\ConfigurationLoader;

require_once __DIR__ . '/inc/queries/class-get-zip-code-query.php';

function register_zipcode_block() {
	$config = [
		'friendly_name'   => 'zippopotam.us',
		'uid'             => 'zippopotamus',
		'endpoint'        => 'https://api.zippopotam.us/us/90210',
		'request_headers' => [
			'Content-Type' => 'application/json',
		],
	];

	$zipcode_query = new GetZipCodeQuery( new HttpDatasource( $config ) );

	ConfigurationLoader::register_block( 'Zip Code', $zipcode_query );
}
add_action( 'register_remote_data_blocks', __NAMESPACE__ . '\\register_zipcode_block' );
