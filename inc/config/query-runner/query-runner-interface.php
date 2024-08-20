<?php
namespace RemoteDataBlocks\Config;

interface QueryRunnerInterface {
	public function execute( array $input_variables ): array|\WP_Error;
}
