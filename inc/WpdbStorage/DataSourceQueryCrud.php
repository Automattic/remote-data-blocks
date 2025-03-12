<?php declare(strict_types = 1);

namespace RemoteDataBlocks\WpdbStorage;

/**
 * CRUD operations for data source queries stored in WordPress options.
 */
class DataSourceQueryCrud extends WpOptionsConfigStore {
	protected static function get_error_code_prefix(): string {
		return 'data_source_query';
	}
	
	protected static function get_error_message_prefix(): string {
		return 'data source query';
	}
	
	protected static function get_option_name(): string {
		return 'remote_data_blocks_data_source_queries';
	}
	
	protected static function get_config_class_map(): array {
		if ( defined( 'REMOTE_DATA_BLOCKS__DATA_SOURCE_QUERY_CLASSMAP' ) ) {
			return constant( 'REMOTE_DATA_BLOCKS__DATA_SOURCE_QUERY_CLASSMAP' );
		}
		
		return [];
	}
}
