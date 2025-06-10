<?php declare(strict_types=1);

namespace RemoteDataBlocks\Integrations\Mcp\Prompts;

use Automattic\WordpressMcp\Core\RegisterMcpPrompt;
use function add_action;

defined( 'ABSPATH' ) || exit();

/**
 * Class ListDataSourcesPrompt
 *
 * Prompt for listing data sources
 */
class ListDataSourcesPrompt {
	public static function init(): void {
		add_action( 'wordpress_mcp_init', [ __CLASS__, 'register_prompt' ] );
	}

	public static function register_prompt(): void {
		/**
		 * @psalm-suppress UndefinedClass
		 */
		new RegisterMcpPrompt(
			[
				'name' => 'list-remote-data-blocks-data-sources',
				'description' => 'List Remote Data Blocks data sources.',
				'arguments' => [
					[
						'name' => 'data-source-uuid',
						'description' => 'The UUID of the Remote Data Blocks data source to retrieve (optional)',
						'required' => false,
						'type' => 'string',
					],
				],
			],
			[
				[
					'role' => 'user',
					'content' => [
						'type' => 'text',
						'text' => 'List all registered Remote Data Blocks data source',
					],
				],
				[
					'role' => 'user',
					'content' => [
						'type' => 'text',
						'text' => 'List the Remote Data Blocks data source with the UUID: {{data_source_uuid}}',
					],
				],
			],
		);
	}
}
