<?php declare(strict_types = 1);

namespace RemoteDataBlocks\ExampleApi;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\ExampleApi\Queries\ExampleApiQueryRunner;
use function register_remote_data_block;

class ExampleApi {
	private static string $block_title = 'Conference Event';

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
			'service_config' => [
				'__version' => 1,
				'display_name' => 'Example API',
				'endpoint' => 'https://example.com/api/v1', // dummy URL
			],
		] );

		$get_record_query = HttpQuery::from_array( [
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
			'query_runner' => new ExampleApiQueryRunner(),
		] );

		$get_table_query = HttpQuery::from_array( [
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
			'query_runner' => new ExampleApiQueryRunner(),
		] );

		register_remote_data_block( [
			'title' => self::$block_title,
			'queries' => [
				'display' => $get_record_query,
				'list' => $get_table_query,
			],
		] );
	}
}
