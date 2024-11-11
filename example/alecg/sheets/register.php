<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Alecg;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsDataSource;
use RemoteDataBlocks\Logging\LoggerManager;

add_action( 'init', function () {
	// Create data source from admin UI credentials
	$data_source = GoogleSheetsDataSource::from_slug( 'sheets' );

	if ( ! $data_source instanceof GoogleSheetsDataSource ) {
		LoggerManager::instance()->debug( 'Data source not found' );
		return;
	}

	$block_name = 'alecg-sheets';
	$get_sheets_query = new GetSheetsQuery( $data_source );
	$list_sheets_query = new ListSheetsQuery( $data_source );

	register_remote_data_block( $block_name, $get_sheets_query );
	register_remote_data_list_query( $block_name, $list_sheets_query );
	register_remote_data_loop_block( sprintf( '%s-loop', $block_name ), $list_sheets_query );
} );

class GetSheetsQuery extends HttpQueryContext {
	private $dynamic_mappings = [];

	public function get_input_schema(): array {
		return [
			'row_id' => [
				'name' => 'Row ID',
				'overrides' => [
					[
						'target' => 'utm_content',
						'type' => 'query_var',
					],
				],
				'type' => 'id',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'root_path' => '$.values[*]',
			'is_collection' => true,
			'mappings' => [
				'row_id' => [
					'name' => 'Row ID',
					'path' => '$.RowId',
					'type' => 'id',
				],
				...$this->dynamic_mappings,
			],
		];
	}

	public function get_endpoint( array $input_variables ): string {
		return $this->get_data_source()->get_endpoint() . '/values/Sheet1';
	}

	public function process_response( string $raw_response_data, array $input_variables ): string|array|object|null {
		$parsed_response_data = json_decode( $raw_response_data, true );

		if ( isset( $parsed_response_data['values'] ) && is_array( $parsed_response_data['values'] ) ) {
			$values = $parsed_response_data['values'];

			$columns = array_shift( $values );
			$first_row = count( $values ) > 0 ? $values[0] : false;
			$this->dynamic_mappings = alecg_infer_sheet_mappings( $columns, $first_row );

			$parsed_response_data['values'] = array_map(
				function ( $row, $index ) use ( $columns ) {
					$combined = array_combine( $columns, $row );
					$combined['RowId'] = $index + 1; // Add row_id field, starting from 1
					return $combined;
				},
				$values,
				array_keys( $values )
			);

			// Reload schema with dynamic mappings
			$this->output_schema = $this->get_output_schema();
		}

		return $parsed_response_data;
	}
}

class ListSheetsQuery extends HttpQueryContext {
	private $dynamic_mappings = [];

	public function get_output_schema(): array {
		return [
			'root_path' => '$.values[*]',
			'is_collection' => true,
			'mappings' => [
				'row_id' => [
					'name' => 'Row ID',
					'path' => '$.RowId',
					'type' => 'id',
				],
				...$this->dynamic_mappings,
			],
		];
	}

	public function get_endpoint( array $input_variables ): string {
		return $this->get_data_source()->get_endpoint() . '/values/Sheet1';
	}

	public function process_response( string $raw_response_data, array $input_variables ): string|array|object|null {
		$parsed_response_data = json_decode( $raw_response_data, true );

		if ( isset( $parsed_response_data['values'] ) && is_array( $parsed_response_data['values'] ) ) {
			$values = $parsed_response_data['values'];

			$columns = array_shift( $values );
			$first_row = count( $values ) > 0 ? $values[0] : false;
			$this->dynamic_mappings = alecg_infer_sheet_mappings( $columns, $first_row );

			$parsed_response_data['values'] = array_map(
				function ( $row, $index ) use ( $columns ) {
					$combined = array_combine( $columns, $row );
					$combined['RowId'] = $index + 1; // Add row_id field, starting from 1
					return $combined;
				},
				$values,
				array_keys( $values )
			);

			// Reload schema with dynamic mappings
			$this->output_schema = $this->get_output_schema();
		}

		return $parsed_response_data;
	}
}

function alecg_infer_sheet_mappings( $columns_row, $first_row ) {
	if ( ! is_array( $columns_row ) || ! is_array( $first_row ) ) {
		return [];
	}

	$dynamic_mappings = [];

	array_map(
		function ( $column_name, $first_row_value ) use ( &$dynamic_mappings ) {
			if ( is_numeric( $first_row_value ) ) {
				$column_type = 'number';
			} else {
				$column_type = 'string';
			}

			// Allow spaces with $['Column Name'] syntax. Escape single quotes.
			$column_name_quote_sanitized = str_replace( "'", "\'", $column_name );

			$column_name_slugified = sanitize_title( $column_name );

			$dynamic_mappings[ $column_name_slugified ] = [
				'name' => $column_name,
				'path' => sprintf( "$['%s']", $column_name_quote_sanitized ),
				'type' => $column_type,
			];
		},
		$columns_row,
		$first_row
	);

	return $dynamic_mappings;
}
