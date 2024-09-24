<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GoogleSheets\WesterosHouses;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class GetWesterosHousesQuery extends HttpQueryContext {
	const COLUMNS = [
		'House',
		'Seat',
		'Region',
		'Words',
		'Sigil',
	];

	public array $input_variables = [
		'row_id' => [
			'name'      => 'Row ID',
			'overrides' => [
				[
					'target' => 'utm_content',
					'type'   => 'query_var',
				],
			],
			'type'      => 'id',
		],
	];

	public array $output_variables = [
		'is_collection' => false,
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

	public function get_endpoint( array $input_variables ): string {
		return $this->get_datasource()->get_endpoint() . '/values/Houses';
	}

	public function process_response( string $raw_response_data, array $input_variables ): string|array|object|null {
		$parsed_response_data = json_decode( $raw_response_data, true );
		$selected_row         = null;
		$row_id               = $input_variables['row_id'];

		if ( isset( $parsed_response_data['values'] ) && is_array( $parsed_response_data['values'] ) ) {
			$raw_selected_row = $parsed_response_data['values'][ $row_id ];
			if ( is_array( $raw_selected_row ) ) {
				$selected_row          = array_combine( self::COLUMNS, $raw_selected_row );
				$selected_row          = array_combine( self::COLUMNS, $selected_row );
				$selected_row['RowId'] = $row_id;
			}
		}

		return $selected_row;
	}
}
