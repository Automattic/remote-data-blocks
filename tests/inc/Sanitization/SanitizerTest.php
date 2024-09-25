<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Sanitization;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Sanitization\Sanitizer;

class SanitizerTest extends TestCase {
	public function test_sanitize_string() {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'name' => [ 'type' => 'string' ],
			],
		];
		$data   = [ 'name' => ' John Doe ' ];
		
		$sanitizer = new Sanitizer( $schema );
		$result    = $sanitizer->sanitize( $data );
		
		$this->assertSame( 'John Doe', $result['name'] );
	}

	public function test_sanitize_integer() {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'age' => [ 'type' => 'integer' ],
			],
		];
		$data   = [ 'age' => '25' ];
		
		$sanitizer = new Sanitizer( $schema );
		$result    = $sanitizer->sanitize( $data );
		
		$this->assertSame( 25, $result['age'] );
	}

	public function test_sanitize_boolean() {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'is_active' => [ 'type' => 'boolean' ],
			],
		];
		$data   = [ 'is_active' => 1 ];
		
		$sanitizer = new Sanitizer( $schema );
		$result    = $sanitizer->sanitize( $data );
		
		$this->assertSame( true, $result['is_active'] );
	}

	public function test_sanitize_array() {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'tags' => [ 'type' => 'array' ],
			],
		];
		$data   = [ 'tags' => [ 'php', ' javascript ', 'python ' ] ];
		
		$sanitizer = new Sanitizer( $schema );
		$result    = $sanitizer->sanitize( $data );
		
		$this->assertSame( [ 'php', 'javascript', 'python' ], $result['tags'] );
	}

	public function test_sanitize_nested_array() {
		$schema = [ 
			'type'       => 'object',
			'properties' => [
				'users' => [
					'type'  => 'array',
					'items' => [
						'name' => [ 'type' => 'string' ],
						'age'  => [ 'type' => 'integer' ],
					],
				],
			],
		];
		$data   = [
			'users' => [
				[
					'name' => ' Alice ',
					'age'  => '30',
				],
				[
					'name' => ' Bob ',
					'age'  => '25',
				],
			],
		];
		
		$sanitizer = new Sanitizer( $schema );
		$result    = $sanitizer->sanitize( $data );
		
		$expected = [
			'users' => [
				[
					'name' => 'Alice',
					'age'  => 30,
				],
				[
					'name' => 'Bob',
					'age'  => 25,
				],
			],
		];
		$this->assertSame( $expected, $result );
	}

	public function test_sanitize_object() {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'user' => [
					'type'       => 'object',
					'properties' => [
						'name' => [ 'type' => 'string' ],
						'age'  => [ 'type' => 'integer' ],
					],
				],
			],
		];
		$data   = [
			'user' => [
				'name' => ' John Doe ',
				'age'  => '30',
			],
		];
		
		$sanitizer = new Sanitizer( $schema );
		$result    = $sanitizer->sanitize( $data );
		
		$expected = [
			'user' => [
				'name' => 'John Doe',
				'age'  => 30,
			],
		];
		$this->assertSame( $expected, $result );
	}

	public function test_sanitize_with_custom_sanitizer() {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'email' => [
					'type'     => 'string',
					'sanitize' => function ( $value ) {
						return strtolower( trim( $value ) );
					},
				],
			],
		];
		$data   = [ 'email' => ' User@Example.com ' ];
		
		$sanitizer = new Sanitizer( $schema );
		$result    = $sanitizer->sanitize( $data );
		
		$this->assertSame( 'user@example.com', $result['email'] );
	}

	public function test_sanitize_ignores_undefined_fields() {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'name' => [ 'type' => 'string' ],
			],
		];
		$data   = [
			'name' => 'John Doe',
			'age'  => 30,
		];
		
		$sanitizer = new Sanitizer( $schema );
		$result    = $sanitizer->sanitize( $data );
		
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayNotHasKey( 'age', $result );
	}

	public function test_sanitize_complex_nested_structure() {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'company' => [
					'type'       => 'object',
					'properties' => [
						'name'      => [ 'type' => 'string' ],
						'employees' => [
							'type'  => 'array',
							'items' => [
								'name'     => [ 'type' => 'string' ],
								'position' => [ 'type' => 'string' ],
								'skills'   => [ 'type' => 'array' ],
							],
						],
					],
				],
			],
		];
		$data   = [
			'company' => [
				'name'      => ' Acme Corp ',
				'employees' => [
					[
						'name'     => ' Alice Smith ',
						'position' => ' Developer ',
						'skills'   => [ ' PHP ', 'JavaScript', ' Python ' ],
					],
					[
						'name'     => ' Bob Johnson ',
						'position' => ' Designer ',
						'skills'   => [ ' UI/UX ', 'Photoshop', ' Illustrator ' ],
					],
				],
			],
		];
		
		$sanitizer = new Sanitizer( $schema );
		$result    = $sanitizer->sanitize( $data );
		
		$expected = [
			'company' => [
				'name'      => 'Acme Corp',
				'employees' => [
					[
						'name'     => 'Alice Smith',
						'position' => 'Developer',
						'skills'   => [ 'PHP', 'JavaScript', 'Python' ],
					],
					[
						'name'     => 'Bob Johnson',
						'position' => 'Designer',
						'skills'   => [ 'UI/UX', 'Photoshop', 'Illustrator' ],
					],
				],
			],
		];
		$this->assertSame( $expected, $result );
	}
}
