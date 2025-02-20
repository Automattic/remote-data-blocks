<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\ArraySerializable;
use RemoteDataBlocks\Validation\Types;

class MockSerializableClass extends ArraySerializable {
	public static function get_config_schema(): array {
		return Types::object( [
			'boolean_value' => Types::boolean(),
			'enum_value' => Types::enum( 'foo', 'bar' ),
			'string_value' => Types::string(),
		] );
	}
}
