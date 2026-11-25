<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use WP_Error;

class BindingFallbackTest extends TestCase {
	private const ID = 'campaign%20April';
	private const CORRUPTED_ID = 'campaignApril';
	private const SAVED_ATTRIBUTE = '<em>Saved%20attribute</em>';

	public static function provideSavedFields(): array {
		return [
			'descriptor during outage' => [ 'id', 'id', self::ID, true, self::ID ],
			'descriptor with missing live field' => [ 'id', 'id', self::ID, false, self::ID ],
			'descriptor after query removal' => [ null, 'id', self::ID, true, self::ID ],
			'flat row with ID schema' => [ 'id', null, self::ID, true, self::ID ],
			'migrated row with ID schema' => [ 'id', 'unknown', self::ID, true, self::ID ],
			'flat row after query removal' => [ null, null, self::ID, true, self::CORRUPTED_ID ],
			'flat object field cannot declare source type' => [
				null,
				null,
				[
					'type' => 'id',
					'value' => self::ID,
				],
				true,
				self::CORRUPTED_ID,
			],
			'non-ID descriptor' => [ 'string', 'string', '<em>' . self::ID . '</em>', true, self::CORRUPTED_ID ],
			'HTML descriptor stays text-filtered' => [ 'html', 'html', '<em>' . self::ID . '</em>', true, self::CORRUPTED_ID ],
			'null descriptor falls back to attribute' => [ 'id', 'id', null, true, 'Savedattribute' ],
			'empty descriptor beats saved attribute' => [ 'id', 'id', '', true, '' ],
			'legacy zero beats saved attribute' => [ 'id', null, 0, true, '0' ],
			'false beats saved attribute' => [ 'boolean', 'boolean', false, true, '' ],
		];
	}

	/** @dataProvider provideSavedFields */
	public function testResolvesSavedFieldWhenLiveValueIsUnavailable( ?string $query_type, ?string $saved_type, mixed $saved_value, bool $offline, string $expected ): void {
		$block_title = 'Test fallback ' . wp_generate_uuid4();
		$block_name = ConfigStore::get_block_name( $block_title );
		$field = 'campaign_key';
		$runner = new class(null, []) extends QueryRunner {
			public bool $offline;
			public int $requests = 0;

			protected function get_raw_response_data( array $request_details, array $input_variables ): array|WP_Error {
				++$this->requests;
				if ( $this->offline ) {
					return new WP_Error( 'offline' );
				}
				return [
					'input_variables' => $input_variables,
					'metadata' => [ 'status_code' => 200 ],
					'response_data' => [],
				];
			}
		};
		$runner->offline = $offline;

		if ( null !== $query_type ) {
			$data_source = HttpDataSource::from_array( [
				'display_name' => 'Fallback API',
				'endpoint' => 'https://example.com/items',
			] );
			$this->assertInstanceOf( HttpDataSource::class, $data_source );
			$query = HttpQuery::from_array( [
				'data_source' => $data_source,
				'output_schema' => [
					'type' => [
						$field => [
							'name' => 'Campaign',
							'type' => $query_type,
						],
					],
				],
				'query_runner' => $runner,
			] );
			$this->assertInstanceOf( HttpQuery::class, $query );
			$this->assertTrue( register_remote_data_block( [
				'title' => $block_title,
				'render_query' => [ 'query' => $query ],
			] ) );
		}

		$saved_row = [ $field => $saved_value ];
		if ( null !== $saved_type ) {
			$saved_row = [
				'uuid' => wp_generate_uuid4(),
				'result' => [
					$field => [
						'name' => 'Campaign',
						'type' => $saved_type,
						'value' => $saved_value,
					],
				],
			];
		}
		$block = [
			'name' => 'core/paragraph',
			'attributes' => [ 'content' => self::SAVED_ATTRIBUTE ],
			'context' => [
				BlockBindings::$context_name => [
					'blockName' => $block_name,
					'queryInputs' => [ [] ],
					'results' => [ $saved_row ],
				],
			],
		];

		$this->assertSame( $expected, BlockBindings::get_value( [ 'field' => $field ], $block, 'content' ) );
		$this->assertSame( null === $query_type ? 0 : 1, $runner->requests );
	}
}
