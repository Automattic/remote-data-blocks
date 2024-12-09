<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryRunner;

use JsonPath\JsonObject;
use RemoteDataBlocks\Sanitization\Sanitizer;

defined( 'ABSPATH' ) || exit();

/**
 * QueryResponseParser class
 */
final class QueryResponseParser {
	/**
	 * Get a primitive field value based on its type.
	 *
	 * @param mixed $field_value The field value.
	 * @param string $field_type The field type.
	 * @param mixed  $default_value The default value.
	 * @return mixed The sanitized field value.
	 */
	private function get_primitive_field_value( mixed $field_value, string $type_name, mixed $default_value = null ): mixed {
		return Sanitizer::sanitize_primitive_type( $type_name, $field_value ) ?? $default_value;
	}

	/**
	 * Parse the response data, adhering to the output schema defined by the query.
	 * Note that "schema" here refers to the simple array structure defined by the
	 * query's "output_schema" config field. This is not the more formal grammar
	 * used to validate the query's config (including the output schema itself).
	 *
	 * This method is recursive.
	 *
	 * @param mixed $data Response data.
	 * @param array $schema The schema to parse the response data.
	 * @return null|array<int, array{
	 *   result: array{
	 *     name: string,
	 *     type: string,
	 *     value: string,
	 *   },
	 * }>
	 */
	public function parse( mixed $data, array $schema ): mixed {
		$json_obj = $data instanceof JsonObject ? $data : new JsonObject( $data );
		$value = $json_obj->get( $schema['path'] ?? '$' );

		if ( is_array( $schema['type'] ?? null ) ) {
			$value = $this->parse_response_objects( $value, $schema['type'] ) ?? [];
		} elseif ( is_string( $schema['type'] ?? null ) ) {
			$value = array_map( function ( $item ) use ( $schema ) {
				return $this->get_primitive_field_value( $item, $schema['type'], $schema['default_value'] ?? null );
			}, $value );
		} else {
			$value = [];
		}

		$is_collection = $schema['is_collection'] ?? false;
		return $is_collection ? $value : $value[0] ?? null;
	}

	private function parse_response_objects( mixed $objects, array $type ): array {
		if ( ! is_array( $objects ) ) {
			return [];
		}

		// Loop over the provided objects and parse it according to the provided schema type.
		return array_map( function ( $object ) use ( $type ) {
			$json_obj = new JsonObject( $object );
			$result = [];

			// Loop over the defined fields in the schema type and extract the values from the object.
			foreach ( $type as $field_name => $mapping ) {
				if ( array_key_exists( 'generate', $mapping ) && is_callable( $mapping['generate'] ) ) {
					$field_value = call_user_func( $mapping['generate'], json_decode( $json_obj->getJson(), true ) );
				} else {
					$field_schema = array_merge( $mapping, [ 'path' => $mapping['path'] ?? "$.{$field_name}" ] );
					$field_value = $this->parse( $json_obj, $field_schema );
				}

				$result[ $field_name ] = [
					'name' => $mapping['name'] ?? $field_name,
					// Convert complex types to string representation.
					'type' => is_string( $mapping['type'] ) ? $mapping['type'] : 'object',
					'value' => $field_value,
				];
			}

			// Nest result property to reserve additional meta in the future.
			return [
				'result' => $result,
			];
		}, $objects );
	}
}
