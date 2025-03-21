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
}
