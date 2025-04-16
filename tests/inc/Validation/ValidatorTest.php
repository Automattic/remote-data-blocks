<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Validation;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Tests\Mocks\MockSerializableClass;
use RemoteDataBlocks\Tests\Mocks\MockSerializableSubclass;
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

			'button_text' => Types::button_text(),
			'button_url' => Types::button_url(),
			'currency_in_current_locale' => Types::currency_in_current_locale(),
			'email_address' => Types::email_address(),
			'html' => Types::html(),
			'id' => Types::id(),
			'image_alt' => Types::image_alt(),
			'image_url' => Types::image_url(),
			'json_path' => Types::json_path(),
			'markdown' => Types::markdown(),
			'title' => Types::title(),
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

			'button_text' => 'Click me',
			'button_url' => 'https://example.com/action',
			'currency_in_current_locale' => '$42.00',
			'email_address' => 'me@example.com',
			'html' => '<p>Hello, world!</p>',
			'id' => '123',
			'image_alt' => 'A tree',
			'image_url' => 'https://example.com/image.jpg',
			'json_path' => '$.foo.bar',
			'markdown' => '# Hello, world!',
			'title' => 'A Title',
			'url' => 'https://example.com/foo',
			'uuid' => '123e4567-e89b-12d3-a456-426614174000',
		] ) );
	}

	public static function provideBooleans(): array {
		return [
			[ true ],
			[ false ],
		];
	}

	public static function provideBooleanLikeStrings(): array {
		return [
			[ 'true' ],
			[ 'false' ],
		];
	}

	public static function provideEmptyStrings(): array {
		return [
			[ '' ],
		];
	}

	public static function provideFloats(): array {
		return [
			[ 3.14 ],
			[ -3.14 ],
			[ 0.0 ],
			[ INF ],
			[ -INF ],
			[ NAN ],
		];
	}

	public static function provideInvalidEmailAddresses(): array {
		return [
			[ 'me@example' ],
			[ '@example.com' ],
			[ 'me@.com' ],
			[ 'me@example.' ],
			[ 'me@.example.com' ],
			[ 'me@ex ample.com' ],
			[ 'me@ex' . str_repeat( 'a', 64 ) . '.com' ],
		];
	}

	public static function provideInvalidUrls(): array {
		return [
			[ 'example.com' ],
			[ '127.0.0.1' ],
			[ 'http:\\\\example.com' ],
			[ 'http:///example.com' ],
			[ 'http:://example.com' ],
			[ 'tel:5551234567' ],
		];
	}

	public static function provideIntegers(): array {
		return [
			[ 0 ],
			[ -1 ],
			[ 42 ],
			[ PHP_INT_MAX ],
			[ PHP_INT_MIN ],
			[ 0x7FFFFFFF ],
			[ 0x80000000 ],
			[ 0x7FFFFFFFFFFFFFFF ],
			[ 0x8000000000000000 ],
		];
	}

	public static function provideNulls(): array {
		return [
			[ null ],
		];
	}

	public static function provideNumericStrings(): array {
		return [
			[ '-1' ],
			[ '0' ],
			[ '1' ],
			[ '3.14' ],
		];
	}

	public static function provideObjectLikes(): array {
		return [
			[ [] ],
			[ [ 'foo' => 'bar' ] ],
			[ (object) [] ],
			[ (object) [ 'foo' => 'bar' ] ],
		];
	}

	public static function provideStrings(): array {
		return [
			[ 'foo' ],
			[ '<p>Hello, world!</p>' ],
			[ 'https://example.com/foo' ],
			[ 'alice@example.com' ],
			[ '123e4567-e89b-12d3-a456-426614174000' ],
		];
	}

	/**
	 * @dataProvider provideBooleanLikeStrings
	 * @dataProvider provideEmptyStrings
	 * @dataProvider provideFloats
	 * @dataProvider provideIntegers
	 * @dataProvider provideInvalidEmailAddresses
	 * @dataProvider provideInvalidUrls
	 * @dataProvider provideNulls
	 * @dataProvider provideNumericStrings
	 * @dataProvider provideObjectLikes
	 * @dataProvider provideStrings
	 */
	public function testInvalidBooleans( mixed $invalid_value ): void {
		$validator = new Validator( Types::boolean(), 'BooleanValidator', '$boolean_value' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$boolean_value must be a boolean', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleanLikeStrings
	 * @dataProvider provideBooleans
	 * @dataProvider provideEmptyStrings
	 * @dataProvider provideFloats
	 * @dataProvider provideInvalidEmailAddresses
	 * @dataProvider provideInvalidUrls
	 * @dataProvider provideNulls
	 * @dataProvider provideNumericStrings
	 * @dataProvider provideObjectLikes
	 * @dataProvider provideStrings
	 */
	public function testInvalidIntegers( mixed $invalid_value ): void {
		$validator = new Validator( Types::integer(), 'IntegerValidator', '$integer_value' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$integer_value must be a integer', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleanLikeStrings
	 * @dataProvider provideBooleans
	 * @dataProvider provideEmptyStrings
	 * @dataProvider provideFloats
	 * @dataProvider provideIntegers
	 * @dataProvider provideInvalidEmailAddresses
	 * @dataProvider provideInvalidUrls
	 * @dataProvider provideNumericStrings
	 * @dataProvider provideObjectLikes
	 * @dataProvider provideStrings
	 */
	public function testInvalidNulls( mixed $invalid_value ): void {
		$validator = new Validator( Types::null(), 'NullValidator', '$null_value' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$null_value must be a null', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleanLikeStrings
	 * @dataProvider provideBooleans
	 * @dataProvider provideEmptyStrings
	 * @dataProvider provideInvalidEmailAddresses
	 * @dataProvider provideInvalidUrls
	 * @dataProvider provideNulls
	 * @dataProvider provideObjectLikes
	 * @dataProvider provideStrings
	 */
	public function testInvalidNumbers( mixed $invalid_value ): void {
		$validator = new Validator( Types::number(), 'NumberValidator', '$number_value' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$number_value must be a number', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleans
	 * @dataProvider provideFloats
	 * @dataProvider provideIntegers
	 * @dataProvider provideNulls
	 * @dataProvider provideObjectLikes
	 */
	public function testInvalidStrings( mixed $invalid_value ): void {
		$validator = new Validator( Types::string(), 'StringValidator', '$string_value' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$string_value must be a string', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleans
	 * @dataProvider provideEmptyStrings
	 * @dataProvider provideFloats
	 * @dataProvider provideIntegers
	 * @dataProvider provideNulls
	 * @dataProvider provideObjectLikes
	 */
	public function testInvalidButtonTexts( mixed $invalid_value ): void {
		$validator = new Validator( Types::button_text(), 'ButtonTextValidator', '$button_text' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$button_text must be a button_text', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleanLikeStrings
	 * @dataProvider provideBooleans
	 * @dataProvider provideEmptyStrings
	 * @dataProvider provideFloats
	 * @dataProvider provideIntegers
	 * @dataProvider provideInvalidEmailAddresses
	 * @dataProvider provideInvalidUrls
	 * @dataProvider provideNulls
	 * @dataProvider provideNumericStrings
	 * @dataProvider provideObjectLikes
	 */
	public function testInvalidButtonUrls( mixed $invalid_value ): void {
		$validator = new Validator( Types::button_url(), 'ButtonUrlValidator', '$button_url' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$button_url must be a button_url', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleans
	 * @dataProvider provideNulls
	 * @dataProvider provideObjectLikes
	 */
	public function testInvalidCurrencyInCurrentLocales( mixed $invalid_value ): void {
		$validator = new Validator( Types::currency_in_current_locale(), 'CurrencyInCurrentLocaleValidator', '$currency_in_current_locale' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$currency_in_current_locale must be a currency_in_current_locale', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleanLikeStrings
	 * @dataProvider provideBooleans
	 * @dataProvider provideEmptyStrings
	 * @dataProvider provideFloats
	 * @dataProvider provideIntegers
	 * @dataProvider provideInvalidEmailAddresses
	 * @dataProvider provideInvalidUrls
	 * @dataProvider provideNulls
	 * @dataProvider provideNumericStrings
	 * @dataProvider provideObjectLikes
	 */
	public function testInvalidEmailAddresses( mixed $invalid_value ): void {
		$validator = new Validator( Types::email_address(), 'EmailAddressValidator', '$email_address' );

		$result = $validator->validate( $invalid_value );
		$this->assertinstanceof( wp_error::class, $result );
		$this->assertstringstartswith( '$email_address must be a email_address', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleans
	 * @dataProvider provideFloats
	 * @dataProvider provideIntegers
	 * @dataProvider provideNulls
	 * @dataProvider provideObjectLikes
	 */
	public function testInvalidHtmls( mixed $invalid_value ): void {
		$validator = new Validator( Types::html(), 'HtmlValidator', '$html' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$html must be a html', $result->get_error_message() );
	}

	/**
	 * @dataProvider provideBooleans
	 * @dataProvider provideEmptyStrings
	 * @dataProvider provideFloats
	 * @dataProvider provideIntegers
	 * @dataProvider provideNulls
	 * @dataProvider provideObjectLikes
	 */
	public function testInvalidIds( mixed $invalid_value ): void {
		$validator = new Validator( Types::id(), 'IdValidator', '$id' );

		$result = $validator->validate( $invalid_value );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringStartsWith( '$id must be a id', $result->get_error_message() );
	}

	public function testInvalidNonPrimitiveType(): void {
		$schema = [ '@type' => 'invented' ];

		$validator = new Validator( $schema, 'InvalidNonPrimitiveValidator', '$invalid_non_primitive' );

		$result = $validator->validate( 'hello, world!' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( '$invalid_non_primitive is unknown type "invented"', $result->get_error_message() );
	}

	public function testInvalidPrimitiveType(): void {
		$schema = [
			'@primitive' => true,
			'@type' => 'invented',
		];

		$validator = new Validator( $schema, 'InvalidPrimitiveValidator', '$invalid_primitive' );

		$result = $validator->validate( 'hello, world!' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( '$invalid_primitive is unknown type "invented"', $result->get_error_message() );
	}

	public function testCallable(): void {
		$schema = Types::callable();

		$validator = new Validator( $schema, 'CallableValidator', '$callable' );

		$this->assertTrue( $validator->validate( 'is_string' ) );
		$this->assertTrue( $validator->validate( function (): string {
			return 'foo';
		} ) );
		$this->assertTrue( $validator->validate( [ $this, 'testCallable' ] ) );

		$result = $validator->validate( 'foo' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( '$callable must be callable', $result->get_error_message() );
	}

	public function testConst(): void {
		$schema = Types::const( 'foo' );

		$validator = new Validator( $schema, 'ConstValidator', '$const' );

		$this->assertTrue( $validator->validate( 'foo' ) );

		$result = $validator->validate( 'bar' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( '$const must equal the constant "foo"', $result->get_error_message() );
	}

	public function testEnum(): void {
		$schema = Types::enum( 'foo', 'bar' );

		$validator = new Validator( $schema, 'EnumValidator', '$enum' );

		$this->assertTrue( $validator->validate( 'foo' ) );
		$this->assertTrue( $validator->validate( 'bar' ) );

		$result = $validator->validate( 'baz' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( '$enum must be one of the enumerated values: foo, bar', $result->get_error_message() );
	}

	public function testInstanceOf(): void {
		$schema = Types::instance_of( self::class );

		$validator = new Validator( $schema, 'InstanceOfValidator', '$instance_of' );

		$this->assertTrue( $validator->validate( $this ) );

		$result = $validator->validate( new stdClass() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( '$instance_of must be an instance of class "RemoteDataBlocks\Tests\Validation\ValidatorTest"', $result->get_error_message() );
	}

	public function testOneOf(): void {
		$schema = Types::one_of( Types::string(), Types::integer() );

		$validator = new Validator( $schema, 'OneOfValidator', '$one_of' );

		$this->assertTrue( $validator->validate( 'foo' ) );
		$this->assertTrue( $validator->validate( 42 ) );

		$result = $validator->validate( null );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( '$one_of must match one of the specified types: string, integer', $result->get_error_message() );
	}

	public function testListOfObjects(): void {
		$schema = Types::list_of(
			Types::object( [
				'a_string' => Types::string(),
			] )
		);

		$validator = new Validator( $schema, 'ListOfObjectsValidator', '$list_of_objects' );

		$this->assertTrue( $validator->validate( [
			[ 'a_string' => 'foo' ],
			[ 'a_string' => 'bar' ],
		] ) );

		$result = $validator->validate( [
			[ 'a_string' => 'foo' ],
			[ 'a_string' => 42 ],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$list_of_objects[1]['a_string'] must be a string", $result->get_error_message() );
		$result = $validator->validate( [
			[ 'a_string' => 'foo' ],
			'foo',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( '$list_of_objects[1] must be an associative array', $result->get_error_message() );
	}

	public function testNullableString(): void {
		$nullable_validator = new Validator( Types::nullable( Types::string() ), 'NullableStringValidator', '$nullable_string' );

		$this->assertTrue( $nullable_validator->validate( null ) );
		$this->assertTrue( $nullable_validator->validate( 'foo' ) );
	}

	public function testObject(): void {
		$schema = Types::object( [
			'a_string' => Types::string(),
			'maybe_a_string' => Types::nullable( Types::string() ),
		] );

		$validator = new Validator( $schema, 'ObjectValidator', '$object' );

		$this->assertTrue( $validator->validate( [ 'a_string' => 'foo' ] ) );
		$this->assertTrue( $validator->validate( [
			'a_string' => 'foo',
			'maybe_a_string' => 'foo',
		] ) );

		$result = $validator->validate( [ 'a_string' => 42 ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$object['a_string'] must be a string", $result->get_error_message() );

		$result = $validator->validate( [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$object['a_string'] must be a string", $result->get_error_message() );
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

		$validator = new Validator( $schema, 'NestedObjectValidator', '$nested_object' );

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
		$this->assertSame( "\$nested_object['nested1']['nested2']['list_of_objects'][1]['a_boolean'] must be a boolean", $result->get_error_message() );
	}

	public function testRecord(): void {
		$schema = Types::record(
			Types::string(),
			Types::integer()
		);

		$validator = new Validator( $schema, 'RecordValidator', '$record' );

		$this->assertTrue( $validator->validate( [ 'record_id' => 123 ] ) );
		$this->assertTrue( $validator->validate( [
			'record_id' => 123,
			'foo' => 42,
		] ) );
		$this->assertTrue( $validator->validate( [] ) );

		$result = $validator->validate( [ 'record_id' => '123' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$record['record_id'] must be a integer", $result->get_error_message() );
	}

	public function testObjectRef(): void {
		$schema = Types::object( [
			'foo' => Types::create_ref(
				'my-ref',
				Types::object( [
					'a_string' => Types::string(),
				] )
			),
			'bar' => Types::use_ref( 'my-ref' ),
		] );

		$validator = new Validator( $schema, 'RefValidator', '$object_ref' );

		$this->assertTrue( $validator->validate( [
			'foo' => [ 'a_string' => 'foo' ],
			'bar' => [ 'a_string' => 'bar' ],
		] ) );

		$result = $validator->validate( [
			'foo' => [ 'a_string' => 'foo' ],
			// Missing 'bar'
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$object_ref['bar'] must be an associative array", $result->get_error_message() );

		$result = $validator->validate( [
			'foo' => [ 'a_string' => 'foo' ],
			'bar' => [ 'a_string' => null ], // Invalid
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$object_ref['bar']['a_string'] must be a string", $result->get_error_message() );
	}

	public function testSerializedConfigFor(): void {
		$schema = Types::object( [
			'config' => Types::serialized_config_for( MockSerializableClass::class ),
		] );

		$validator = new Validator( $schema, 'SerializedConfigValidator', '$serialized_config' );

		$this->assertTrue( $validator->validate( [
			'config' => [
				'__class' => MockSerializableClass::class,
				'boolean_value' => true,
				'enum_value' => 'foo',
				'string_value' => 'hello, world!',
			],
		] ) );

		$result = $validator->validate( [
			'config' => [
				'__class' => MockSerializableClass::class,
				'boolean_value' => 'NOT A BOOLEAN',
				'enum_value' => 'foo',
				'string_value' => 'hello, world!',
			],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$serialized_config['config']['boolean_value'] must be a boolean", $result->get_error_message() );

		$result = $validator->validate( [
			'config' => null,
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$serialized_config['config'] must be an associative array", $result->get_error_message() );

		$result = $validator->validate( [
			'config' => new stdClass(),
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$serialized_config['config'] must be an associative array", $result->get_error_message() );

		$result = $validator->validate( [
			'config' => [
				'__class' => MockSerializableClass::class,
			],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$serialized_config['config']['boolean_value'] must be a boolean", $result->get_error_message() );
	}

	public function testSerializedConfigForSubclass(): void {
		$schema = Types::object( [
			'config' => Types::serialized_config_for( MockSerializableClass::class ),
		] );

		$validator = new Validator( $schema, 'SerializedConfigForSubclassValidator', '$serialized_config' );

		$this->assertTrue( $validator->validate( [
			'config' => [
				'__class' => MockSerializableSubclass::class,
				'boolean_value' => true,
				'enum_value' => 'foo',
				'string_value' => 'hello, world!',
				'extra_value' => 'required for subclass',
			],
		] ) );

		$result = $validator->validate( [
			'config' => [
				'__class' => MockSerializableSubclass::class,
				'boolean_value' => true,
				'enum_value' => 'foo',
				'string_value' => 'hello, world!',
			],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( "\$serialized_config['config']['extra_value'] must be a string", $result->get_error_message() );
	}

	public function testStringMatching(): void {
		$schema = Types::string_matching( '/^foo$/' );

		$validator = new Validator( $schema, 'StringMatchingValidator', '$string_matching' );

		$this->assertTrue( $validator->validate( 'foo' ) );

		$result = $validator->validate( 'bar' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( '$string_matching must match regex "/^foo$/"', $result->get_error_message() );
	}
}
