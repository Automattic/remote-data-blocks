<?php declare(strict_types = 1);

/**
 * Gets private property of a class.
 *
 * @param class-string $class_name Name of the class.
 * @param string       $property_name Name of the property.
 * @return ReflectionProperty
 */
function get_private_property( string $class_name, string $property_name ): ReflectionProperty {
	$reflector = new ReflectionClass( $class_name );
	$property = $reflector->getProperty( $property_name );

	/**
	 * @psalm-suppress UnusedMethodCall
	 */
	$property->setAccessible( true );

	return $property;
}

/**
 * Overrides the value of a private property on a given object. This is
 * useful when mocking the internals of a class.
 *
 * Note that the property will no longer be private after setAccessible is
 * called.
 *
 * @param class-string $class_name The fully qualified class name, including namespace.
 * @param object       $object_instance The object instance on which to set the value.
 * @param string       $property_name The name of the private property to override.
 * @param mixed        $value The value to set.
 */
function set_private_property(
	string $class_name,
	$object_instance,
	string $property_name,
	$value
): void {
	$property = get_private_property( $class_name, $property_name );
	$property->setValue( $object_instance, $value );
}

/**
 * Gets private method of a class.
 *
 * @param class-string $class_name Name of the class.
 * @param string       $method Name of the method.
 * @return ReflectionMethod
 */
function get_private_method( string $class_name, string $method ): ReflectionMethod {
	$reflector = new ReflectionClass( $class_name );
	$method = $reflector->getMethod( $method );

	/**
	 * @psalm-suppress UnusedMethodCall
	 */
	$method->setAccessible( true );

	return $method;
}
