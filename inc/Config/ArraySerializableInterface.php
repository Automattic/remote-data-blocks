<?php

namespace RemoteDataBlocks\Config;

use WP_Error;

interface ArraySerializableInterface {
	public static function from_array( array $config ): static|WP_Error;

	public function to_array(): array;
}
