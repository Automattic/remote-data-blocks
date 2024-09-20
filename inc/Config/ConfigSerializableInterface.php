<?php

namespace RemoteDataBlocks\Config;

use RemoteDataBlocks\Validation\ValidatorInterface;
use WP_Error;

interface ConfigSerializableInterface {
	public static function from_array( array $config ): static|WP_Error;

	public function to_array(): array;
}
