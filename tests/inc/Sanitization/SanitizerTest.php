<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Sanitization;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Sanitization\Sanitizer;
use RemoteDataBlocks\Validation\Types;

class SanitizerTest extends TestCase {
	public static function provideSanitizableStrings(): array {
		return [
			[ ' John Doe ', 'John Doe' ],
			[ ' Jane Doe', 'Jane Doe' ],
			[ 'Alice', 'Alice' ],
			[ ' Bob ', 'Bob' ],
			[ '  Charlie  ', 'Charlie' ],
			[ [ [ 'John Doe' ], 'Jane Doe', 33 ], 'John Doe' ],
			[ 0, '0' ],
			[ null, '' ],
			[ true, '1' ],
			[ false, '' ],
			[ [], '' ],
			[ '', '' ],
			[ '   ', '' ],
		];
	}

	/**
	 * @dataProvider provideSanitizableStrings
	 */
	public function test_sanitize_string( mixed $value, string $expected ): void {
		$schema = Types::object( [
			'name' => Types::string(),
		] );

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( [ 'name' => $value ] );

		$this->assertSame( $expected, $result['name'] );
	}

	public static function provideSanitizableIntegers(): array {
		return [
			[ '25', 25 ],
			[ 25, 25 ],
			[ '0', 0 ],
			[ 0, 0 ],
			[ '-10', -10 ],
			[ -10, -10 ],
			[ null, 0 ],
			[ [], 0 ],
			[ false, 0 ],
		];
	}

	/**
	 * @dataProvider provideSanitizableIntegers
	 */
	public function test_sanitize_integer( mixed $value, int $expected ): void {
		$schema = Types::object( [
			'age' => Types::integer(),
		] );
		$data = [ 'age' => $value ];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( $expected, $result['age'] );
	}

	public static function provideSanitizableBooleans(): array {
		return [
			[ true, true ],
			[ 'true', true ],
			[ 'false', true ],
			[ 1, true ],
			[ -1, true ],
			[ 100, true ],
			[ false, false ],
			[ '', false ],
			[ [], false ],
			[ 0, false ],
			[ null, false ],
		];
	}

	/**
	 * @dataProvider provideSanitizableBooleans
	 */
	public function test_sanitize_boolean( mixed $value, bool $expected ): void {
		$schema = Types::object( [
			'is_active' => Types::boolean(),
		] );
		$data = [ 'is_active' => $value ];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( $expected, $result['is_active'] );
	}

	public static function provideSanitizableNullableBooleans(): array {
		return [
			[ true, true ],
			[ 1, true ],
			[ false, false ],
			[ 0, false ],
			[ null, null ],
		];
	}

	/**
	 * @dataProvider provideSanitizableNullableBooleans
	 */
	public function test_sanitize_nullable_boolean( mixed $value, bool|null $expected ): void {
		$schema = Types::object( [
			'is_active' => Types::nullable( Types::boolean() ),
		] );
		$data = [ 'is_active' => $value ];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( $expected, $result['is_active'] );
	}

	public function test_sanitize_any(): void {
		$schema = Types::object( [
			'one' => Types::any(),
			'two' => Types::any(),
			'three' => Types::any(),
			'four' => Types::any(),
			'five' => Types::any(),
		] );
		$data = [
			'one' => 'string',
			'two' => 123,
			'three' => true,
			'four' => [ 'array' ],
			'five' => null,
		];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( $data, $result );
	}

	public function test_sanitize_array(): void {
		$schema = Types::object( [
			'tags' => Types::list_of( Types::string() ),
		] );
		$data = [ 'tags' => [ 'php', ' javascript ', 'python ' ] ];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( [ 'php', 'javascript', 'python' ], $result['tags'] );
	}

	public function test_sanitize_nested_array(): void {
		$schema = Types::object( [
			'users' => Types::list_of(
				Types::object( [
					'name' => Types::string(),
					'age' => Types::integer(),
				] )
			),
		] );
		$data = [
			'users' => [
				[
					'name' => ' Alice ',
					'age' => '30',
				],
				[
					'name' => ' Bob ',
					'age' => '25',
				],
			],
		];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$expected = [
			'users' => [
				[
					'name' => 'Alice',
					'age' => 30,
				],
				[
					'name' => 'Bob',
					'age' => 25,
				],
			],
		];
		$this->assertSame( $expected, $result );
	}

	public function test_sanitize_nested_array_of_objects(): void {
		$schema = Types::object( [
			'users' => Types::list_of(
				Types::object( [
					'name' => Types::string(),
					'age' => Types::integer(),
				] )
			),
		] );
		$data = [
			'users' => [
				[
					'name' => 'Alice',
					'age' => 30,
					'additional_unknown_field' => 'unknown_value',
				],
				[
					'name' => 'Bob',
					'age' => 25,
				],
			],
		];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$expected = [
			'users' => [
				[
					'name' => 'Alice',
					'age' => 30,
				],
				[
					'name' => 'Bob',
					'age' => 25,
				],
			],
		];
		$this->assertSame( $expected, $result );
	}

	public function test_sanitize_not(): void {
		$schema = Types::record(
			Types::string(),
			Types::not( Types::callable() )
		);
		$data = [
			'string' => 'string',
			'number' => 123,
			'boolean' => true,
			'list_of' => [ 'array' ],
			'null' => null,
		];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( $data, $result );
	}

	public function test_sanitize_object(): void {
		$schema = Types::object( [
			'user' => Types::object( [
				'name' => Types::string(),
				'age' => Types::integer(),
			] ),
		] );
		$data = [
			'user' => [
				'name' => ' John Doe ',
				'age' => '30',
			],
		];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$expected = [
			'user' => [
				'name' => 'John Doe',
				'age' => 30,
			],
		];
		$this->assertSame( $expected, $result );
	}

	public function test_sanitize_record(): void {
		$schema = Types::record(
			Types::string(),
			Types::object( [
				'name' => Types::string(),
				'age' => Types::integer(),
			] ),
		);
		$data = [
			'123' => [
				'name' => ' John Doe ',
				'age' => '30',
			],
		];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$expected = [
			'123' => [
				'name' => 'John Doe',
				'age' => 30,
			],
		];
		$this->assertSame( $expected, $result );
	}

	public function test_sanitize_removes_undefined_fields(): void {
		$schema = Types::object( [
			'name' => Types::string(),
		] );
		$data = [
			'name' => 'John Doe',
			'age' => 30,
		];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayNotHasKey( 'age', $result );
	}

	public function test_sanitize_complex_nested_structure(): void {
		$schema = Types::object( [
			'company' => Types::object( [
				'name' => Types::string(),
				'employees' => Types::list_of(
					Types::object( [
						'name' => Types::string(),
						'position' => Types::string(),
						'skills' => Types::list_of( Types::string() ),
					] )
				),
			] ),
		] );
		$data = [
			'company' => [
				'name' => ' Acme Corp ',
				'employees' => [
					[
						'name' => ' Alice Smith ',
						'position' => ' Developer ',
						'skills' => [ ' PHP ', 'JavaScript', ' Python ' ],
					],
					[
						'name' => ' Bob Johnson ',
						'position' => ' Designer ',
						'skills' => [ ' UI/UX ', 'Photoshop', ' Illustrator ' ],
					],
				],
			],
		];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$expected = [
			'company' => [
				'name' => 'Acme Corp',
				'employees' => [
					[
						'name' => 'Alice Smith',
						'position' => 'Developer',
						'skills' => [ 'PHP', 'JavaScript', 'Python' ],
					],
					[
						'name' => 'Bob Johnson',
						'position' => 'Designer',
						'skills' => [ 'UI/UX', 'Photoshop', 'Illustrator' ],
					],
				],
			],
		];
		$this->assertSame( $expected, $result );
	}

	public function test_skip_sanitize_string(): void {
		$schema = Types::object( [
			'password' => Types::skip_sanitize( Types::string() ),
		] );
		$data = [ 'password' => ' John Doe ' ];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( ' John Doe ', $result['password'] );
	}

	/**
	 * A value that must never survive sanitization as markup.
	 */
	private const UNSAFE_MARKUP = '<img src=x onerror=alert(1)>Name';

	/**
	 * Primitive types that intentionally pass markup through unchanged. Every
	 * consumer of these types is responsible for escaping or sandboxing the
	 * value (wp_kses on the front end, SandBox iframe in the editor).
	 *
	 * Adding a type here is a security decision; do it deliberately.
	 */
	private const RAW_PRIMITIVE_TYPES = [ 'html', 'json_path', 'markdown' ];

	/**
	 * Discover every sanitizable primitive type from the Types class so that a
	 * newly added type is covered without touching this test.
	 */
	public static function provideSanitizablePrimitiveTypes(): array {
		$cases = [];
		$reflection = new \ReflectionClass( Types::class );

		foreach ( $reflection->getMethods( \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC ) as $method ) {
			if ( $method->getNumberOfRequiredParameters() > 0 ) {
				continue;
			}

			try {
				$type = $method->invoke( null );
				if ( ! is_array( $type ) || ! Types::is_primitive( $type ) || ! Types::is_sanitizable( $type ) ) {
					continue;
				}
			} catch ( \Throwable $_e ) {
				continue;
			}

			$type_name = Types::get_type_name( $type );
			$cases[ $type_name ] = [ $type_name ];
		}

		return $cases;
	}

	/**
	 * @dataProvider provideSanitizablePrimitiveTypes
	 */
	public function test_every_primitive_type_has_a_sanitization_decision( string $type_name ): void {
		$result = Sanitizer::sanitize_primitive_type( $type_name, self::UNSAFE_MARKUP );

		if ( in_array( $type_name, self::RAW_PRIMITIVE_TYPES, true ) ) {
			$this->assertSame(
				self::UNSAFE_MARKUP,
				$result,
				sprintf( 'Type "%s" is listed as raw and must pass markup through unchanged.', $type_name )
			);
			return;
		}

		$this->assertStringNotContainsString(
			'<',
			strval( $result ),
			sprintf( 'Type "%s" must not pass markup through. Add a case to Sanitizer::sanitize_primitive_type() or list it in RAW_PRIMITIVE_TYPES.', $type_name )
		);
	}

	public function test_raw_primitive_types_are_real_types(): void {
		$known = array_keys( self::provideSanitizablePrimitiveTypes() );

		foreach ( self::RAW_PRIMITIVE_TYPES as $type_name ) {
			$this->assertContains( $type_name, $known, sprintf( 'RAW_PRIMITIVE_TYPES lists unknown type "%s".', $type_name ) );
		}
	}

	public function test_unknown_primitive_type_is_sanitized_as_text(): void {
		$result = Sanitizer::sanitize_primitive_type( 'some_future_type', self::UNSAFE_MARKUP );

		$this->assertSame( 'Name', $result );
	}

	public static function provideSanitizableTextTypes(): array {
		return [
			[ 'button_text' ],
			[ 'currency_in_current_locale' ],
			[ 'id' ],
			[ 'image_alt' ],
			[ 'string' ],
			[ 'title' ],
			[ 'uuid' ],
		];
	}

	/**
	 * @dataProvider provideSanitizableTextTypes
	 */
	public function test_sanitize_text_types_strip_markup_and_whitespace( string $type_name ): void {
		$this->assertSame( 'Name', Sanitizer::sanitize_primitive_type( $type_name, self::UNSAFE_MARKUP ) );
		$this->assertSame( 'rec123', Sanitizer::sanitize_primitive_type( $type_name, '  <b>rec123</b>  ' ) );
		$this->assertSame( '0', Sanitizer::sanitize_primitive_type( $type_name, 0 ) );
		$this->assertSame( '', Sanitizer::sanitize_primitive_type( $type_name, null ) );
	}

	public static function provideSanitizableNumbers(): array {
		return [
			[ '25', 25.0 ],
			[ 25, 25.0 ],
			[ '3.14', 3.14 ],
			[ 3.14, 3.14 ],
			[ '-1.5', -1.5 ],
			[ '1e3', 1000.0 ],
			[ '0', 0.0 ],
			[ null, 0.0 ],
			[ false, 0.0 ],
			[ true, 1.0 ],
			[ [], 0.0 ],
			[ [ '2.5', '9' ], 2.5 ],
			[ 'not a number', 0.0 ],
			[ self::UNSAFE_MARKUP, 0.0 ],
		];
	}

	/**
	 * @dataProvider provideSanitizableNumbers
	 */
	public function test_sanitize_number( mixed $value, float $expected ): void {
		$schema = Types::object( [
			'price' => Types::number(),
		] );

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( [ 'price' => $value ] );

		$this->assertSame( $expected, $result['price'] );
	}

	public static function provideRawTypes(): array {
		return [
			[ 'html' ],
			[ 'markdown' ],
		];
	}

	/**
	 * @dataProvider provideRawTypes
	 */
	public function test_sanitize_raw_types_preserve_markup( string $type_name ): void {
		$value = '<p>A <em>formatted</em> value</p>';

		$this->assertSame( $value, Sanitizer::sanitize_primitive_type( $type_name, $value ) );
		$this->assertSame( $value, Sanitizer::sanitize_primitive_type( $type_name, [ $value ] ) );
	}
}
