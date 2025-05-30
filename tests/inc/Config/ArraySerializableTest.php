<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Tests\Mocks\MockSerializableClass;
use WP_Error;

class ArraySerializableTest extends TestCase {
	private array $sample_config = [
		'boolean_value' => true,
		'enum_value' => 'foo',
		'string_value' => 'test',
	];

	public function test_from_array_valid_config(): void {
		$instance = MockSerializableClass::from_array( $this->sample_config );
		$this->assertInstanceOf( MockSerializableClass::class, $instance );
	}

	public function test_from_array_invalid_config(): void {
		$instance = MockSerializableClass::from_array( [ 'foo' => 'bar' ] );
		$this->assertInstanceOf( WP_Error::class, $instance );
	}

	public function test_to_array(): void {
		$instance = MockSerializableClass::from_array( $this->sample_config );
		$config = $instance->to_array();
		$expected_config = array_merge(
			$this->sample_config,
			[ '__class' => MockSerializableClass::class ]
		);

		$this->assertSame( $expected_config, $config );
	}
}
