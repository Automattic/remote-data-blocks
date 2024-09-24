<?php

declare(strict_types = 1);

namespace RemoteDataBlocks\ExampleApi;

use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\ExampleApi\Queries\ExampleApiDataSource;
use RemoteDataBlocks\ExampleApi\Queries\ExampleApiGetRecordQuery;
use RemoteDataBlocks\ExampleApi\Queries\ExampleApiGetTableQuery;
use function register_remote_data_block;
use function register_remote_data_list_query;

class ExampleApi {
	private static string $block_name = 'Conference Event';

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_remote_data_block' ] );
	}

	private static function should_register(): bool {
		/**
		 * Determines whether the example remote data block should be registered.
		 *
		 * @param bool $should_register
		 * @return bool
		 */
		return apply_filters( 'remote_data_blocks_register_example_block', true );
	}

	public static function register_remote_data_block(): void {
		if ( true !== self::should_register() ) {
			return;
		}

		$datasource = ExampleApiDatasource::from_array( [
			'slug'    => 'example-api',
			'service' => 'example_api',
		] );
		
		$get_record_query = new ExampleApiGetRecordQuery( $datasource );
		$get_table_query  = new ExampleApiGetTableQuery( $datasource );

		register_remote_data_block( self::$block_name, $get_record_query );
		register_remote_data_list_query( self::$block_name, $get_table_query );
	}
}
