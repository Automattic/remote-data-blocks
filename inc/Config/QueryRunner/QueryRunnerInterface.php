<?php

declare(strict_types = 1);

namespace RemoteDataBlocks\Config\QueryRunner;

interface QueryRunnerInterface {
	public function execute( array $input_variables ): array|\WP_Error;
}
