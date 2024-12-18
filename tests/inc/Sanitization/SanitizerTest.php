<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Sanitization;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Sanitization\Sanitizer;
use RemoteDataBlocks\Validation\Types;

class SanitizerTest extends TestCase {
	public function test_sanitize_string() {
		$schema = Types::object( [
			'name' => Types::string(),
		] );

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( [ 'name' => ' John Doe ' ] );

		$this->assertSame( 'John Doe', $result['name'] );

		// Takes the first element of the array.
		$result = $sanitizer->sanitize( [ 'name' => [ [ 'John Doe' ], 'Jane Doe', 33 ] ] );

		$this->assertSame( 'John Doe', $result['name'] );
	}

	public function test_sanitize_integer() {
		$schema = Types::object( [
			'age' => Types::integer(),
		] );
		$data = [ 'age' => '25' ];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( 25, $result['age'] );
	}

	public function test_sanitize_boolean() {
		$schema = Types::object( [
			'is_active' => Types::boolean(),
		] );
		$data = [ 'is_active' => 1 ];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( true, $result['is_active'] );
	}

	public function test_sanitize_any() {
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

	public function test_sanitize_array() {
		$schema = Types::object( [
			'tags' => Types::list_of( Types::string() ),
		] );
		$data = [ 'tags' => [ 'php', ' javascript ', 'python ' ] ];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( [ 'php', 'javascript', 'python' ], $result['tags'] );
	}

	public function test_sanitize_nested_array() {
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

	public function test_sanitize_nested_array_of_objects() {
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


	public function test_sanitize_object() {
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

	public function test_sanitize_record() {
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

	public function test_sanitize_removes_undefined_fields() {
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

	public function test_sanitize_complex_nested_structure() {
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

	public function test_skip_sanitize_string() {
		$schema = Types::object( [
			'password' => Types::skip_sanitize( Types::string() ),
		] );
		$data = [ 'password' => ' John Doe ' ];

		$sanitizer = new Sanitizer( $schema );
		$result = $sanitizer->sanitize( $data );

		$this->assertSame( ' John Doe ', $result['password'] );
	}
}
