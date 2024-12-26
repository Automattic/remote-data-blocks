<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Validation;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Validation\Types;
use RemoteDataBlocks\Validation\Validator;
use stdClass;
use WP_Error;

class ValidatorTest extends TestCase {
	public function testValidPrimitiveTypes(): void {
		$schema = Types::object( [
			'boolean' => Types::boolean(),
			'integer' => Types::integer(),
			'null' => Types::null(),
			'number' => Types::number(),
			'string' => Types::string(),

			'email_address' => Types::email_address(),
			'html' => Types::html(),
			'id' => Types::id(),
			'image_alt' => Types::image_alt(),
			'image_url' => Types::image_url(),
			'json_path' => Types::json_path(),
			'markdown' => Types::html(),
			'url' => Types::url(),
			'uuid' => Types::uuid(),
		] );

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( [
			'boolean' => true,
			'integer' => 42,
			'null' => null,
			'number' => 3.14,
			'string' => 'foo',

			'email_address' => 'me@example.com',
			'html' => '<p>Hello, world!</p>',
			'id' => '123',
			'image_alt' => 'A tree',
			'image_url' => 'https://example.com/image.jpg',
			'json_path' => '$.foo.bar',
			'markdown' => '# Hello, world!',
			'url' => 'https://example.com/foo',
			'uuid' => '123e4567-e89b-12d3-a456-426614174000',
		] ) );
	}

	public function testInvalidBooleans(): void {
		$invalid_booleans = [
			null,
			42,
			3.14,
			'',
			'foo',
			[],
			(object) [],
		];

		$validator = new Validator( Types::boolean() );

		foreach ( $invalid_booleans as $invalid_boolean ) {
			$result = $validator->validate( $invalid_boolean );
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertStringStartsWith( 'Value must be a boolean:', $result->get_error_message() );
		}
	}

	public function testInvalidIntegers(): void {
		$invalid_integers = [
			null,
			true,
			3.14,
			'',
			'foo',
			[],
			(object) [],
		];

		$validator = new Validator( Types::integer() );

		foreach ( $invalid_integers as $invalid_integer ) {
			$result = $validator->validate( $invalid_integer );
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertStringStartsWith( 'Value must be a integer:', $result->get_error_message() );
		}
	}

	public function testInvalidNulls(): void {
		$invalid_nulls = [
			true,
			42,
			3.14,
			'',
			'foo',
			[],
			(object) [],
		];

		$validator = new Validator( Types::null() );

		foreach ( $invalid_nulls as $invalid_null ) {
			$result = $validator->validate( $invalid_null );
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertStringStartsWith( 'Value must be a null:', $result->get_error_message() );
		}
	}

	public function testInvalidNumbers(): void {
		$invalid_numbers = [
			null,
			true,
			'',
			'foo',
			[],
			(object) [],
		];

		$validator = new Validator( Types::number() );

		foreach ( $invalid_numbers as $invalid_number ) {
			$result = $validator->validate( $invalid_number );
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertStringStartsWith( 'Value must be a number:', $result->get_error_message() );
		}
	}

	public function testInvalidStrings(): void {
		$invalid_strings = [
			null,
			true,
			42,
			3.14,
			[ 'foo' ],
			(object) [],
		];

		$validator = new Validator( Types::string() );

		foreach ( $invalid_strings as $invalid_string ) {
			$result = $validator->validate( $invalid_string );
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertStringStartsWith( 'Value must be a string:', $result->get_error_message() );
		}
	}

	public function testInvalidEmailAddresses(): void {
		$invalid_email_addresses = [
			null,
			true,
			42,
			3.14,
			'',
			'foo',
			[],
			(object) [],
			'me@example',
			'@example.com',
			'me@.com',
			'me@example.',
			'me@.example.com',
			'me@ex ample.com',
			'me@ex' . str_repeat( 'a', 64 ) . '.com',
		];

		$validator = new Validator( Types::email_address() );

		foreach ( $invalid_email_addresses as $invalid_email_address ) {
			$result = $validator->validate( $invalid_email_address );
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertStringStartsWith( 'Value must be a email_address:', $result->get_error_message() );
		}
	}

	// TODO additional invalid primitive tests

	public function testCallable(): void {
		$schema = Types::callable();

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( 'is_string' ) );
		$this->assertTrue( $validator->validate( function (): string {
			return 'foo';
		} ) );
		$this->assertTrue( $validator->validate( [ $this, 'testCallable' ] ) );

		$result = $validator->validate( 'foo' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Value must be callable: foo', $result->get_error_message() );
	}

	public function testConst(): void {
		$schema = Types::const( 'foo' );

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( 'foo' ) );

		$result = $validator->validate( 'bar' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Value must be the constant: bar', $result->get_error_message() );
	}

	public function testEnum(): void {
		$schema = Types::enum( 'foo', 'bar' );

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( 'foo' ) );
		$this->assertTrue( $validator->validate( 'bar' ) );

		$result = $validator->validate( 'baz' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Value must be one of the enumerated values: baz', $result->get_error_message() );
	}

	public function testInstanceOf(): void {
		$schema = Types::instance_of( self::class );

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( $this ) );

		$result = $validator->validate( new stdClass() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Value must be an instance of the specified class: {}', $result->get_error_message() );
	}

	public function testOneOf(): void {
		$schema = Types::one_of( Types::string(), Types::integer() );

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( 'foo' ) );
		$this->assertTrue( $validator->validate( 42 ) );

		$result = $validator->validate( null );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Value must be one of the specified types: null', $result->get_error_message() );
	}

	public function testListOfObjects(): void {
		$schema = Types::list_of(
			Types::object( [
				'a_string' => Types::string(),
			] )
		);

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( [
			[ 'a_string' => 'foo' ],
			[ 'a_string' => 'bar' ],
		] ) );

		$result = $validator->validate( [
			[ 'a_string' => 'foo' ],
			[ 'a_string' => 42 ],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Value must be a list of the specified type: {"a_string":42}', $result->get_error_message() );
		$result = $validator->validate( [
			[ 'a_string' => 'foo' ],
			'foo',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Value must be a list of the specified type: foo', $result->get_error_message() );
	}

	public function testNullableString(): void {
		$nullable_validator = new Validator( Types::nullable( Types::string() ) );

		$this->assertTrue( $nullable_validator->validate( null ) );
		$this->assertTrue( $nullable_validator->validate( 'foo' ) );
	}

	public function testObject(): void {
		$schema = Types::object( [
			'a_string' => Types::string(),
			'maybe_a_string' => Types::nullable( Types::string() ),
		] );

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( [ 'a_string' => 'foo' ] ) );
		$this->assertTrue( $validator->validate( [
			'a_string' => 'foo',
			'maybe_a_string' => 'foo',
		] ) );

		$result = $validator->validate( [ 'a_string' => 42 ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Object must have valid property: a_string', $result->get_error_message() );

		$result = $validator->validate( [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Object must have valid property: a_string', $result->get_error_message() );
	}

	public function testNestedObject(): void {
		$schema = Types::object( [
			'nested1' => Types::object( [
				'nested2' => Types::object( [
					'a_string' => Types::string(),
					'list_of_objects' => Types::list_of(
						Types::object( [
							'a_boolean' => Types::boolean(),
						] )
					),
				] ),
			] ),
		] );

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( [
			'nested1' => [
				'nested2' => [
					'a_string' => 'foo',
					'list_of_objects' => [
						[ 'a_boolean' => true ],
						[ 'a_boolean' => false ],
					],
				],
			],
		] ) );

		$result = $validator->validate( [
			'nested1' => [
				'nested2' => [
					'a_string' => 'foo',
					'list_of_objects' => [
						[ 'a_boolean' => true ],
						[ 'a_boolean' => 'foo' ], // Invalid
					],
				],
			],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Object must have valid property: nested1', $result->get_error_message() );
	}

	public function testRecord(): void {
		$schema = Types::record(
			Types::string(),
			Types::integer()
		);

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( [ 'record_id' => 123 ] ) );
		$this->assertTrue( $validator->validate( [
			'record_id' => 123,
			'foo' => 42,
		] ) );
		$this->assertTrue( $validator->validate( [] ) );

		$result = $validator->validate( [ 'record_id' => '123' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Record must have valid value: 123', $result->get_error_message() );
	}

	public function testRef(): void {
		$schema = Types::object( [
			'foo' => Types::create_ref(
				'my-ref',
				Types::object( [
					'a_string' => Types::string(),
				] )
			),
			'bar' => Types::use_ref( 'my-ref' ),
		] );

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( [
			'foo' => [ 'a_string' => 'foo' ],
			'bar' => [ 'a_string' => 'bar' ],
		] ) );

		$result = $validator->validate( [
			'foo' => [ 'a_string' => 'foo' ],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Object must have valid property: bar', $result->get_error_message() );

		$result = $validator->validate( [
			'foo' => [ 'a_string' => 'foo' ],
			'bar' => [ 'a_string' => null ], // Invalid
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Object must have valid property: bar', $result->get_error_message() );
	}

	public function testStringMatching(): void {
		$schema = Types::string_matching( '/^foo$/' );

		$validator = new Validator( $schema );

		$this->assertTrue( $validator->validate( 'foo' ) );

		$result = $validator->validate( 'bar' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Value must match the specified regex: bar', $result->get_error_message() );
	}
}
