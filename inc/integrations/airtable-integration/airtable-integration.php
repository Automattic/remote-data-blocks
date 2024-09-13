<?php

namespace RemoteDataBlocks\Integrations;

use RemoteDataBlocks\Config\AirtableDatasource;
use RemoteDataBlocks\REST\DatasourceCRUD;

require_once __DIR__ . '/datasources/airtable-datasource.php';

class AirtableIntegration {
	public static function init(): void {
		self::register_dynamic_data_source_blocks();
	}

	private static function register_dynamic_data_source_blocks(): void {
		$data_sources = DatasourceCRUD::get_data_sources( REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE );

		foreach ( $data_sources as $config ) {
			// Transform data to our experimental format, which is all array based
			$config = array_map(
				function ( $value ) {
					return is_object( $value ) ? (array) $value : $value;
				},
				(array) $config
			);
			self::register_blocks_for_airtable_data_source( $config );
		}
	}

	private static function register_blocks_for_airtable_data_source( array $config ): void {
		$airtable_datasource = new AirtableDatasource( $config['token'], $config['base'], $config['tables'] );
		// TODO...
	}
}
