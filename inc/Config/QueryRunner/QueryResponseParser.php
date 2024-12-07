<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryRunner;

use JsonPath\JsonObject;

defined( 'ABSPATH' ) || exit();

/**
 * QueryResponseParser class
 */
final class QueryResponseParser {
	/**
	 * Get the field value based on the field type. This method casts the field
	 * value to a string (since this will ultimately be used as block content).
	 *
	 * @param array|string $field_value   The field value.
	 * @param string       $default_value The default value.
	 * @param string       $field_type    The field type.
	 * @return string The field value.
	 */
	private static function get_field_value( mixed $field_value, string $default_value, string $type_name ): string {
		if ( ! is_string( $field_value ) || empty( $field_value ) ) {
			return $default_value;
		}

		switch ( $type_name ) {
			case 'base64_string':
				return base64_decode( $field_value );

			case 'html':
				return $field_value;

			case 'price':
				return sprintf( '$%s', number_format( (float) $field_value, 2 ) );

			case 'string':
				return wp_strip_all_tags( $field_value );
		}

		return $field_value;
	}

	/**
	 * Parse theresponse data, adhering to the output schema defined by the query.
	 * This method is recursive.
	 *
	 * @param mixed $data Response data.
	 * @return null|array<int, array{
	 *   result: array{
	 *     name: string,
	 *     type: string,
	 *     value: string,
	 *   },
	 * }>
	 */
	public static function parse( mixed $data, array $schema ): mixed {
		$json_obj = $data instanceof JsonObject ? $data : new JsonObject( $data );
		$value = $json_obj->get( $schema['path'] ?? '$' );

		if ( is_array( $schema['type'] ) && ! array_is_list( $schema['type'] ) ) {
			$value = self::parse_response_objects( $value, $schema['type'] ) ?? [];
		} else {
			$value = array_map( function ( $item ) use ( $schema ) {
				return self::get_field_value( $item, $schema['default_value'] ?? '', $schema['type'] );
			}, $value );
		}

		$is_collection = $schema['is_collection'] ?? false;
		return $is_collection ? $value : $value[0] ?? null;
	}

	private static function parse_response_objects( mixed $objects, array $type ): array {
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
					$field_value = self::parse( $json_obj, $field_schema );
				}

				$result[ $field_name ] = [
					'name' => $mapping['name'] ?? $field_name,
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
