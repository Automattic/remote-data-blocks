<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Validation;

use RemoteDataBlocks\Validation\Types;
use RemoteDataBlocks\Config\DataSource\HttpDataSourceInterface;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;

/**
 * ConfigSchemas class.
 */
final class ConfigSchemas {
	public static function get_graphql_query_config_schema(): array {
		static $schema = null;

		if ( null === $schema ) {
			$schema = self::generate_graphql_query_config_schema();
		}

		return $schema;
	}

	public static function get_http_data_source_config_schema(): array {
		static $schema = null;

		if ( null === $schema ) {
			$schema = self::generate_http_datasource_config_schema();
		}

		return $schema;
	}

	public static function get_http_query_config_schema(): array {
		static $schema = null;

		if ( null === $schema ) {
			$schema = self::generate_http_query_config_schema();
		}

		return $schema;
	}

	private static function generate_graphql_query_config_schema(): array {
		return Types::merge_object_types(
			self::get_http_query_config_schema(),
			Types::object( [
				'graphql_query' => Types::string(),
				'graphql_query_variables' => Types::nullable(
					Types::one_of(
						Types::callable(),
						Types::record(
							Types::string(),
							Types::one_of( Types::boolean(), Types::integer(), Types::null(), Types::string() )
						),
					)
				),
				'request_method' => Types::nullable( Types::enum( 'GET', 'POST' ) ),
			] )
		);
	}

	private static function generate_http_datasource_config_schema(): array {
		return Types::object( [
			'__metadata' => Types::nullable(
				Types::object( [
					'created_at' => Types::integer(),
					'updated_at' => Types::integer(),
				] )
			),
			'display_name' => Types::string(),
			'endpoint' => Types::string(),
			'image_url' => Types::nullable( Types::image_url() ),
			'request_headers' => Types::nullable(
				Types::one_of(
					Types::callable(),
					Types::record( Types::string(), Types::string() ),
				)
			),
			'service' => Types::string(),
			'service_schema_version' => Types::integer(),
			'slug' => Types::string_matching( '/^[a-z0-9-]+$/' ),
			'uuid' => Types::nullable( Types::uuid() ),
		] );
	}

	private static function generate_http_query_config_schema(): array {
		return Types::object( [
			'cache_ttl' => Types::nullable( Types::one_of( Types::callable(), Types::integer(), Types::null() ) ),
			'data_source' => Types::instance_of( HttpDataSourceInterface::class ),
			'endpoint' => Types::nullable( Types::one_of( Types::callable(), Types::url() ) ),
			'image_url' => Types::nullable( Types::image_url() ),
			// Input schema is simple: just a flat map of string keys and primitive values.
			'input_schema' => Types::nullable(
				Types::record(
					Types::string(),
					Types::object( [
						'default_value' => Types::nullable( Types::string() ), // TODO should match property type. any type with special validation?
						'name' => Types::string(),
						'type' => Types::enum( 'boolean', 'id', 'integer', 'string' ),
					] ),
				)
			),
			//  This is a schema for a schema, so it will get complicated!
			'output_schema' => Types::create_ref(
				'FIELD_SCHEMA',
				Types::object( [
					'default_value' => Types::nullable( Types::string() ), // TODO should match property type. any type with special validation?
					'generate' => Types::nullable( Types::callable() ),
					'is_collection' => Types::nullable( Types::boolean() ),
					'name' => Types::nullable( Types::string() ),
					'path' => Types::nullable( Types::json_path() ),
					'type' => Types::one_of(
						Types::enum( 'base64_string', 'boolean', 'image_alt', 'image_url', 'integer', 'price', 'string' ),
						Types::record( Types::string(), Types::use_ref( 'FIELD_SCHEMA' ) ), // Nested schema!
					),
				] ),
			),
			'preprocess_response' => Types::nullable( Types::callable() ),
			'query_key' => Types::string(),
			'query_name' => Types::nullable( Types::string() ),
			'query_runner' => Types::nullable( Types::instance_of( QueryRunnerInterface::class ) ),
			'request_body' => Types::nullable(
				Types::one_of(
					Types::callable(),
					Types::object( [] ),
				)
			),
			'request_headers' => Types::nullable(
				Types::one_of(
					Types::callable(),
					Types::record( Types::string(), Types::string() ),
				)
			),
			'request_method' => Types::nullable( Types::enum( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ) ),
		] );
	}
}
