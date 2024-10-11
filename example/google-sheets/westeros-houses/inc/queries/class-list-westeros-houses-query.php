<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GoogleSheets\WesterosHouses;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class ListWesterosHousesQuery extends HttpQueryContext {
	const COLUMNS = [
		'House',
		'Seat',
		'Region',
		'Words',
		'Sigil',
	];

	public function get_output_schema(): array {
		return [
			'root_path'     => '$.values[*]',
			'is_collection' => true,
			'mappings'      => [
				'row_id'    => [
					'name' => 'Row ID',
					'path' => '$.RowId',
					'type' => 'id',
				],
				'house'     => [
					'name' => 'House',
					'path' => '$.House',
					'type' => 'string',
				],
				'seat'      => [
					'name' => 'Seat',
					'path' => '$.Seat',
					'type' => 'string',
				],
				'region'    => [
					'name' => 'Region',
					'path' => '$.Region',
					'type' => 'string',
				],
				'words'     => [
					'name' => 'Words',
					'path' => '$.Words',
					'type' => 'string',
				],
				'image_url' => [
					'name' => 'Sigil',
					'path' => '$.Sigil',
					'type' => 'image_url',
				],
			],
		];
	}

	public function get_endpoint( array $input_variables ): string {
		return $this->get_data_source()->get_endpoint() . '/values/Houses';
	}

	public function process_response( string $raw_response_data, array $input_variables ): string|array|object|null {
		$parsed_response_data = json_decode( $raw_response_data, true );

		if ( isset( $parsed_response_data['values'] ) && is_array( $parsed_response_data['values'] ) ) {
			$values = $parsed_response_data['values'];
			array_shift( $values ); // Drop the first row

			$parsed_response_data['values'] = array_map(
				function ( $row, $index ) {
					$combined          = array_combine( self::COLUMNS, $row );
					$combined['RowId'] = $index + 1; // Add row_id field, starting from 1
					return $combined;
				},
				$values,
				array_keys( $values )
			);
		}

		return $parsed_response_data;
	}
}
