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
			$schema = self::generate_http_data_source_config_schema();
		}

		return $schema;
	}

	public static function get_http_data_source_service_config_schema(): array {
		static $schema = null;

		if ( null === $schema ) {
			$schema = self::generate_http_data_source_service_config_schema();
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
				'request_method' => Types::nullable( Types::enum( 'GET', 'POST' ) ),
			] )
		);
	}

	private static function generate_http_data_source_config_schema(): array {
		return Types::object( [
			'display_name' => Types::string(),
			'endpoint' => Types::string(),
			'image_url' => Types::nullable( Types::image_url() ),
			'request_headers' => Types::nullable(
				Types::one_of(
					Types::callable(),
					Types::record( Types::string(), Types::string() ),
				)
			),
			'service' => Types::nullable( Types::string() ),
			'service_config' => Types::nullable( Types::record( Types::string(), Types::any() ) ),
			'uuid' => Types::nullable( Types::uuid() ),
		] );
	}

	private static function generate_http_data_source_service_config_schema(): array {
		return Types::object( [
			'__version' => Types::integer(),
			'auth' => Types::nullable(
				Types::object( [
					'add_to' => Types::nullable( Types::enum( 'header', 'query' ) ),
					'key' => Types::nullable( Types::skip_sanitize( Types::string() ) ),
					'type' => Types::enum( 'basic', 'bearer', 'api-key', 'none' ),
					'value' => Types::skip_sanitize( Types::string() ),
				] )
			),
			'display_name' => Types::string(),
			'endpoint' => Types::url(),
		] );
	}

	private static function generate_http_query_config_schema(): array {
		return Types::object( [
			'cache_ttl' => Types::nullable( Types::one_of( Types::callable(), Types::integer(), Types::null() ) ),
			'data_source' => Types::instance_of( HttpDataSourceInterface::class ),
			'endpoint' => Types::nullable( Types::one_of( Types::callable(), Types::url() ) ),
			'image_url' => Types::nullable( Types::image_url() ),
			// NOTE: The "input schema" for a query is not a formal schema like the
			// ones generated by this class. It is a simple flat map of string keys and
			// primitive values that can be encoded as a PHP associative array or
			// another serializable data structure like JSON.
			'input_schema' => Types::nullable(
				Types::record(
					Types::string(),
					Types::object( [
						// TODO: The default value type should match the type specified for
						// the current field, but we have no grammar to represent this (refs
						// are global, not scoped). We could create a Types::matches_sibling
						// helper to handle this, or just do it ad hoc when we validate the
						// input schema.
						'default_value' => Types::nullable( Types::any() ),
						'name' => Types::nullable( Types::string() ),
						// NOTE: These values are string references to the "core primitive
						// types" from our formal schema. Referencing these types allows us
						// to use the same validation and sanitization logic.
						'type' => Types::enum( 'boolean', 'id', 'integer', 'null', 'number', 'string' ),
					] ),
				)
			),
			// NOTE: The "output schema" for a query is not a formal schema like the
			// ones generated by this class. It is, however, more complex than the
			// "input schema" so that it can represent nested data structures.
			//
			// Since we want this "schema" to be serializable and simple to use, we
			// have created our own shorthand syntax that effectively maps to our more
			// formal types. The formal schema below describes this shorthand syntax.
			//
			// This allows most "output schemas" to be represented as a PHP associative
			// array or another serializable data structure like JSON (unless it uses
			// unseriazable types like closures).
			'output_schema' => Types::create_ref(
				'FIELD_SCHEMA',
				Types::object( [
					// @see Note above about default value type.
					'default_value' => Types::nullable( Types::any() ),
					'format' => Types::nullable( Types::callable() ),
					'generate' => Types::nullable( Types::callable() ),
					'is_collection' => Types::nullable( Types::boolean() ),
					'name' => Types::nullable( Types::string() ),
					'path' => Types::nullable( Types::json_path() ),
					'type' => Types::one_of(
						// NOTE: These values are string references to all of the primitive
						// types from our formal schema. Referencing these types allows us to
						// use the same validation and sanitization logic for both. This list
						// must not contain non-primitive types, because this simple syntax
						// cannot accept type arguments.
						Types::enum(
							'boolean',
							'integer',
							'null',
							'number',
							'string',
							'button_text',
							'button_url',
							'currency_in_current_locale',
							'email_address',
							'html',
							'id',
							'image_alt',
							'image_url',
							'markdown',
							// 'json_path' is omitted since it likely has no user utility.
							'url',
							'uuid',
						),
						Types::record( Types::string(), Types::use_ref( 'FIELD_SCHEMA' ) ), // Nested schema!
					),
				] ),
			),
			'preprocess_response' => Types::nullable( Types::callable() ),
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
