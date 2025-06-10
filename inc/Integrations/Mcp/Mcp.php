<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Mcp;

defined( 'ABSPATH' ) || exit();

class Mcp {
	public static function init(): void {
		Features\ListDataSourcesFeature::init();
		Prompts\ListDataSourcesPrompt::init();
	}
}
