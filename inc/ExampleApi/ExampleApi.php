<?php declare(strict_types = 1);

namespace RemoteDataBlocks\ExampleApi;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\ExampleApi\Queries\ExampleApiQueryRunner;

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

		$data_source = HttpDataSource::from_array( [
			'display_name' => 'Example API',
			'endpoint' => 'https://example.com/api/v1', // dummy URL
			'slug' => 'example-api',
			'service' => 'example_api',
		] );

		$get_record_query = HttpQueryContext::from_array( [
			'data_source' => $data_source,
			'input_schema' => [
				'record_id' => [
					'name' => 'Record ID',
					'type' => 'id',
				],
			],
			'output_schema' => [
				'type' => [
					'id' => [
						'name' => 'Record ID',
						'path' => '$.id',
						'type' => 'id',
					],
					'title' => [
						'name' => 'Title',
						'path' => '$.fields.Activity',
						'type' => 'string',
					],
					'location' => [
						'name' => 'Location',
						'path' => '$.fields.Location',
						'type' => 'string',
					],
					'event_type' => [
						'name' => 'Event type',
						'path' => '$.fields.Type',
						'type' => 'string',
					],
				],
			],
			'query_name' => 'Get event',
			'query_runner' => new ExampleApiQueryRunner(),
		] );

		$get_table_query = HttpQueryContext::from_array( [
			'data_source' => $data_source,
			'input_schema' => [],
			'output_schema' => [
				'is_collection' => true,
				'path' => '$.records[*]',
				'type' => [
					'record_id' => [
						'name' => 'Record ID',
						'path' => '$.id',
						'type' => 'id',
					],
					'title' => [
						'name' => 'Title',
						'path' => '$.fields.Activity',
						'type' => 'string',
					],
					'location' => [
						'name' => 'Location',
						'path' => '$.fields.Location',
						'type' => 'string',
					],
					'event_type' => [
						'name' => 'Event type',
						'path' => '$.fields.Type',
						'type' => 'string',
					],
				],
			],
			'query_key' => 'example_api_list_events',
			'query_name' => 'List events',
			'query_runner' => new ExampleApiQueryRunner(),
		] );

		register_remote_data_block( self::$block_name, $get_record_query );
		register_remote_data_list_query( self::$block_name, $get_table_query );
	}
}
