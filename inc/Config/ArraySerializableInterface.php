<?php

namespace RemoteDataBlocks\Config;

use WP_Error;

interface ArraySerializableInterface {
	public static function from_array( array $config );

	public function to_array(): array;
}
