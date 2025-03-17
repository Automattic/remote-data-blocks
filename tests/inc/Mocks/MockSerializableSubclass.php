<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Validation\Types;

class MockSerializableSubclass extends MockSerializableClass {
	public static function get_config_schema(): array {
		return Types::object( [
			'boolean_value' => Types::boolean(),
			'enum_value' => Types::enum( 'foo', 'bar' ),
			'string_value' => Types::string(),
			'extra_value' => Types::string(),
		] );
	}
}
