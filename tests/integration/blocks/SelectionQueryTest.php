<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests\Blocks;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\QueryRunner\QueryResponseParser;

class SelectionQueryTest extends TestCase {
	public static function provideIdentifiers(): array {
		return [
			'percent octet' => [ 'campaign%20April', 'campaign%2520April' ],
			'numeric zero' => [ 0, '0' ],
			'string zero' => [ '0', '0' ],
			'leading zeroes' => [ '00042', '00042' ],
			'identifier string' => [ 'record/April & May', 'record%2FApril%20%26%20May' ],
			'surrounding spaces' => [ '  record  ', '%20%20record%20%20' ],
		];
	}

	/** @dataProvider provideIdentifiers */
	public function testSelectedIdReachesDisplayEndpoint( string|int $id, string $expected_path ): void {
		$parser = new QueryResponseParser();
		$results = $parser->parse(
			[ [ 'id' => $id ] ],
			[
				'is_collection' => true,
				'type' => [ 'id' => [ 'type' => 'id' ] ],
			]
		);

		// DataViews selection copies the selected row's field values into query inputs.
		$this->assertIsArray( $results );
		$this->assertIsArray( $results[0]['result'] );
		$inputs = array_map( function ( mixed $field ): mixed {
			$this->assertIsArray( $field );
			return $field['value'];
		}, $results[0]['result'] );

		$data_source = HttpDataSource::from_array( [
			'display_name' => 'Selection API',
			'endpoint' => 'https://example.com/items',
		] );
		$this->assertInstanceOf( HttpDataSource::class, $data_source );

		$query = HttpQuery::from_array( [
			'data_source' => $data_source,
			'input_schema' => [
				'id' => [
					'type' => 'id',
					'required' => true,
				],
			],
			'output_schema' => [ 'type' => [ 'id' => [ 'type' => 'id' ] ] ],
			'endpoint' => static fn ( array $input ): string => 'https://example.com/items/' . rawurlencode( $input['id'] ),
		] );
		$this->assertInstanceOf( HttpQuery::class, $query );

		$this->assertSame( 'https://example.com/items/' . $expected_path, $query->get_endpoint( $inputs ) );
	}
}
